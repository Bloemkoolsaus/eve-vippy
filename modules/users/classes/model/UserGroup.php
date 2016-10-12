<?php
namespace users\model
{
	class UserGroup extends \Model
	{
        protected $_table = "user_groups";

		public $id = 0;
        public $authGroupID;
		public $name;

		private $_rights = null;
        private $_users = null;

		function store()
		{
            parent::store();

			if ($this->_rights !== null)
			{
				\MySQL::getDB()->delete("user_group_rights", array("groupid" => $this->id));
				foreach ($this->getRights() as $module => $rights)
				{
					foreach ($rights as $name => $title)
					{
						// Kijk of dit recht al bekend is.
						if ($right = \MySQL::getDB()->getRow("SELECT * FROM user_rights WHERE module = ? AND name = ?", [$module, $name])) {
							$id = $right["id"];
						} else {
							$id = \MySQL::getDB()->updateinsert("user_rights",
                                    ["module" => $module, "name" => $name, "title"	=> $title],
                                    ["module" => $module, "name" => $name]);
						}

						// Toeveogen
						\MySQL::getDB()->insert("user_group_rights", array("groupid" => $this->id, "rightid" => $id, "level" => 1));
					}
				}
			}
		}

		/**
		 * Get users
		 * @return \users\model\User[]
		 */
		function getUsers()
		{
			if ($this->_users === null)
            {
                $this->_users = [];
                if ($results = \MySQL::getDB()->getRows("SELECT	u.*
                                                        FROM	users u
                                                            INNER JOIN user_user_group ug ON ug.userid = u.id
                                                        WHERE	ug.groupid = ?
                                                        ORDER BY u.displayname"
                                                , [$this->id]))
                {
                    foreach ($results as $result)
                    {
                        $user = new \users\model\User();
                        $user->load($result);
                        $this->_users[] = $user;
                    }
                }
            }

			return $this->_users;
		}

		function getRights()
		{
			if ($this->_rights === null)
			{
				$this->clearRights();
				if ($results = \MySQL::getDB()->getRows("SELECT	r.*
														FROM	user_rights r
															INNER JOIN user_group_rights g ON g.rightid = r.id
														WHERE	g.groupid = ?"
												, array($this->id)))
				{
					foreach ($results as $result) {
						$this->addRight($result["module"], $result["name"], $result["title"]);
					}
				}
			}

			return $this->_rights;
		}

		function clearRights()
		{
			$this->_rights = array();
		}

		function addRight($module, $name, $title)
		{
			if ($this->_rights === null)
				$this->_rights = $this->getRights();

			$this->_rights[$module][$name] = $title;
		}

		function hasRight($module, $name)
		{
			if ($this->_rights === null)
				$this->getRights();

			if (isset($this->_rights[$module][$name]))
				return true;
			else
				return false;
		}

        function getAuthgroup()
        {
            if ($this->authGroupID)
                return new \admin\model\AuthGroup($this->authGroupID);

            return null;
        }
	}
}
?>