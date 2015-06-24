<?php
namespace eve\model
{
	class Character
	{
		public $id = 0;
		public $apiKeyID = 0;
		public $userID = 0;
		public $name;
		public $corporationID = 0;
		public $isDirector = false;
		public $isCEO = false;
		public $titles = array();
		public $skills = array();
		public $dateOfBirth = null;
		public $updatedate;

		private $isAuthorized = null;
		private $corporation = null;
		private $roles = null;
		private $apikey = null;
		private $user = null;

		function __construct($id=false)
		{
			if ($id) {
				$this->id = $id;
				$this->load();
			}
		}

		function load($result=false)
		{
			if (!$result)
				$result = \MySQL::getDB()->getRow("SELECT * FROM characters WHERE id = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["id"];
				$this->apiKeyID = $result["api_keyid"];
				$this->userID = $result["userid"];
				$this->name = $result["name"];
				$this->corporationID = $result["corpid"];
				$this->isDirector = ($result["isdirector"]>0)?true:false;
				$this->isCEO = ($result["isceo"]>0)?true:false;
				$this->dateOfBirth = $result["dob"];
				$this->updatedate = $result["updatedate"];
			}
		}

		function store()
		{
			// Get old data. Check for differences!
			if ($this->id > 0)
			{
				$old = new \eve\model\Character($this->id);

				// Check user
				if ($old->userID != 0 && $old->userID != $this->userID)
				{
					$user = new \users\model\User($this->userID);
					$user->addLog("character-owner-changed", $this->id,
							array(	"character" => $this->id,
									"fromuser"	=> $old->userID,
									"touser"	=> $this->userID));
				}

				// Check API Key
				if ($old->apiKeyID != 0 && $old->apiKeyID != $this->apiKeyID)
				{
					$user = new \users\model\User($this->userID);
					$user->addLog("character-apikey-changed", $this->id,
							array(	"character" => $this->id,
									"fromapi"	=> $old->apiKeyID,
									"toapi"		=> $this->apiKeyID));
				}
			}

			$data = array(	"id"			=> $this->id,
							"name"			=> $this->name,
							"api_keyid"		=> $this->apiKeyID,
							"userid"		=> $this->userID,
							"corpid"		=> $this->corporationID,
							"isdirector"	=> ($this->isDirector())?1:0,
							"isceo"			=> ($this->isCEO())?1:0,
							"dob"			=> ($this->dateOfBirth!=null)?date("Y-m-d H:i:s",strtotime($this->dateOfBirth)):null,
							"updatedate"	=> date("Y-m-d H:i:s"));
			\MySQL::getDB()->updateinsert("characters", $data, array("id" => $this->id));
		}

		function isCEO()
		{
			return $this->isCEO;
		}

		function isDirector()
		{
			if ($this->isCEO())
				return true;
			else
				return $this->isDirector;
		}

		function getTitle()
		{
			if ($this->isCEO())
				return "CEO";

			if ($this->isDirector())
				return "Director";

			if ($this->isFittingManager())
				return "Fitting Manager";

			return "";
		}

		function isFittingManager()
		{
			if ($this->isDirector())
				return true;

			foreach ($this->getRoles() as $role)
			{
				if (strtolower($role) == "rolefittingmanager")
					return true;
			}
			return false;
		}

		function getRoles()
		{
			if ($this->roles === null)
			{
				$this->roles = array();
				if ($results = \MySQL::getDB()->getRows("SELECT * FROM character_roles WHERE characterid = ?", array($this->id)))
				{
					foreach ($results as $result) {
						$this->roles[] = $result["role"];
					}
				}
			}

			return $this->roles;
		}

		function isAuthorized()
		{
			\AppRoot::debug("isAuthorized(".$this->name.")");
			if ($this->isAuthorized === null)
			{
				// Geldige api key??
				if ($this->getApiKey() !== null)
				{
					if ($this->getApiKey()->valid)
					{
						// In een geldige auth-groep?
						$this->isAuthorized = false;
						foreach (\admin\model\AuthGroup::getAuthgroupsByCorporation($this->corporationID) as $group)
						{
							if ($group->isAllowed())
							{
								$this->isAuthorized = true;
								break;
							}
						}

                        if (!$this->isAuthorized)
                            \AppRoot::debug("<span styl'color:red;'>not in an allowed group</span>");
					}
					else
						\AppRoot::debug("<span styl'color:red;'>api ".$this->getApiKey()->id." key not valid</span>");
				}
				else
					\AppRoot::debug("<span style='color:red;'>no api key</span>");
			}

			\AppRoot::debug("valid: ".(($this->isAuthorized)?"yes":"no"));
			return $this->isAuthorized;
		}

		/**
		 * Get character corporation
		 * @return \eve\model\Corporation
		 */
		function getCorporation()
		{
			if ($this->corporation == null)
				$this->corporation = new \eve\model\Corporation($this->corporationID);

			return $this->corporation;
		}

		/**
		 * Get apikey
		 * @return \eve\model\API|null
		 */
		function getApiKey()
		{
			if ($this->apikey == null && $this->apiKeyID > 0)
				$this->apikey = new \eve\model\API($this->apiKeyID);

			return $this->apikey;
		}

		/**
		 * Get user
		 * @return \users\model\User|null
		 */
		function getUser()
		{
			if ($this->user === null && $this->userID > 0)
				$this->user = new \users\model\User($this->userID);

			return $this->user;
		}
	}
}
?>
