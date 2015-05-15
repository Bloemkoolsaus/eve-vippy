<?php
namespace notices\model
{
	class Notice
	{
		public $id = 0;
		public $title;
		public $body;
		public $typeID = 0;
		public $persistant = false;
		public $global = false;
		public $solarSystemID = 0;
		public $userID = 0;
		public $authGroupID = 0;
		public $messageDate = null;
		public $expireDate = null;
		public $deleted = false;

		private $system = null;

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
				$result = \MySQL::getDB()->getRow("SELECT * FROM notices WHERE id = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["id"];
				$this->title = $result["title"];
				$this->body = $result["body"];
				$this->typeID = $result["typeid"];
				$this->persistant = ($result["persistant"]>0)?true:false;
				$this->global = ($result["global"]>0)?true:false;
				$this->solarSystemID = $result["solarsystemid"];
				$this->userID = $result["userid"];
				$this->authGroupID = $result["authgroupid"];
				$this->messageDate = $result["messagedate"];
				$this->expireDate = $result["expiredate"];
				$this->deleted = ($result["deleted"]>0)?true:false;
			}
		}

		function store()
		{
			if ($this->id == 0 && $this->userID == 0)
				$this->userID = (\User::getUSER()) ? \User::getUSER()->id : 0;

			if ($this->authGroupID == null || $this->authGroupID == 0)
				$this->authGroupID = \User::getUSER()->getCurrentAuthGroupID();

			if ($this->messageDate == null)
				$this->messageDate = date("Y-m-d H:i:s");

			if ($this->expireDate == null)
				$this->expireDate = date("Y-m-d", mktime(0,0,0,date("m")+2,0,date("Y")));

			$data = array("title"		=> $this->title,
						"body"			=> $this->body,
						"typeid"		=> $this->typeID,
						"persistant"	=> ($this->persistant)?1:0,
						"global"		=> ($this->global)?1:0,
						"solarsystemid"	=> $this->solarSystemID,
						"userid"		=> $this->userID,
						"authgroupid"	=> $this->authGroupID,
						"messagedate"	=> ($this->messageDate!=null) ? date("Y-m-d H:i:s", strtotime($this->messageDate)) : date("Y-m-d H:i:s"),
						"expiredate"	=> ($this->expireDate!=null) ? date("Y-m-d H:i:s", strtotime($this->expireDate)) : "2020-12-31 00:00:00",
						"deleted"		=> ($this->deleted)?1:0);
			if ($this->id > 0)
				$data["id"] = $this->id;

			$result = \MySQL::getDB()->updateinsert("notices", $data, array("id" => $this->id));
			if ($this->id == 0)
				$this->id = $result;
		}

		function getTitle()
		{
			$title = "";
			if ($this->solarSystemID > 0)
				$title = $this->getSystem()->name.": ".

			$title .= $this->title;
			return $title;
		}

		function getTypeName()
		{
			if ($result = \MySQL::getDB()->getRow("SELECT * FROM notice_types WHERE id = ?", array($this->typeID)))
				return $result["name"];
			else
				return "";
		}

		function markRead($userID=false)
		{
			if (!$userID)
				$userID = (\User::getUSER()) ? \User::getUSER()->id : false;

			if ($userID)
			{
				$data = array("noticeid" => $this->id, "userid" => $userID);
				\MySQL::getDB()->updateinsert("notices_read", $data, $data);
				return true;
			}
			else
				return false;
		}


		/**
		 * Get system
		 * @return \eve\model\SolarSystem
		 */
		function getSystem()
		{
			if ($this->system == null)
				$this->system = new \eve\model\SolarSystem($this->solarSystemID);

			return $this->system;
		}
	}
}
?>