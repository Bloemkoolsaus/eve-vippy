<?php
namespace users\model
{
	class UserGroup
	{
		public $id = 0;
		public $name;
		public $hidden = false;
		public $deleted = false;
		public $updatedate;

		private $rights = null;

		public function __construct($id = false)
		{
			if ($id)
			{
				$this->id = $id;
				$this->load();
			}
		}

		function load($result=false)
		{
			if (!$result)
				$result = \MySQL::getDB()->getRow("SELECT * FROM user_groups WHERE id = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["id"];
				$this->name = $result["name"];
				$this->hidden = ($result["hidden"]>0)?true:false;
				$this->deleted = ($result["deleted"]>0)?true:false;
				$this->updatedate = $result["updatedate"];
			}
		}

		function store()
		{
			$data = array(	"name" 		 => $this->name,
							"hidden" 	 => $this->hidden,
							"deleted" 	 => $this->deleted,
							"updatedate" => date("Y-m-d H:i:s"));
			if ($this->id > 0)
				$data["id"] = $this->id;

			$result = \MySQL::getDB()->updateinsert("user_groups", $data, array("id" => $this->id));
			if ($this->id == 0)
				$this->id = $result;

			if ($this->rights !== null)
			{
				\MySQL::getDB()->delete("user_group_rights", array("groupid" => $this->id));
				foreach ($this->getRights() as $module => $rights)
				{
					foreach ($rights as $name => $title)
					{
						// Kijk of de right bestaat
						if ($right = \MySQL::getDB()->getRow("	SELECT * FROM user_rights
																WHERE module = ? AND name = ?"
													, array($module, $name)))
						{
							$id = $right["id"];
						}
						else
						{
							$id = \MySQL::getDB()->updateinsert("user_rights",
														array("module" => $module, "name" => $name, "title"	=> $title),
														array("module" => $module, "name" => $name));
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
			$users = array();
			if ($results = \MySQL::getDB()->getRows("SELECT	u.*
													FROM	users u
														INNER JOIN user_user_group ug ON ug.userid = u.id
													WHERE	ug.groupid = ?
													ORDER BY u.displayname"
										, array($this->id)))
			{
				foreach ($results as $result)
				{
					$user = new \users\model\User();
					$user->load($result);
					$users[] = $user;
				}
			}

			return $users;
		}

		function getRights()
		{
			if ($this->rights === null)
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

			return $this->rights;
		}

		function clearRights()
		{
			$this->rights = array();
		}

		function addRight($module, $name, $title)
		{
			if ($this->rights === null)
				$this->rights = $this->getRights();

			$this->rights[$module][$name] = $title;
		}

		function hasRight($module, $name)
		{
			if ($this->rights === null)
				$this->getRights();

			if (isset($this->rights[$module][$name]))
				return true;
			else
				return false;
		}
	}
}
?>