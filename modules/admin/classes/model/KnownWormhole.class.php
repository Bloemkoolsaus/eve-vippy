<?php
namespace admin\model
{
	class KnownWormhole
	{
		public $id = 0;
		public $systemID = 0;
		public $name;
		public $status = 0;
		public $authGroupID = 0;

		private $solarsystem = null;

		function __construct($id=false)
		{
			if ($id) {
				$this->systemID = $id;
				$this->authGroupID = \User::getUSER()->getCurrentAuthGroupID();
				$this->load();
			}
		}

		function load($result=false)
		{
			if (!$result)
			{
				$result = \MySQL::getDB()->getRow("	SELECT 	*
													FROM 	mapknownwormholes
													WHERE 	solarsystemid = ?
													AND		authgroupid = ?"
								, array($this->systemID, $this->authGroupID));
			}

			if ($result)
			{
				$this->id = $result["id"];
				$this->systemID = $result["solarsystemid"];
				$this->name = $result["name"];
				$this->status = $result["status"];
				$this->authGroupID = $result["authgroupid"];
			}
		}

		function store()
		{
			$data = array(	"solarsystemid"	=> $this->systemID,
							"name"			=> $this->name,
							"status"		=> $this->status,
							"authgroupid"	=> $this->authGroupID);
			if ($this->id > 0)
				$data["id"] = $this->id;

			$result = \MySQL::getDB()->updateinsert("mapknownwormholes", $data, array("id" => $this->id));
			if ($this->id == 0)
				$this->id = $result;
		}

		function delete()
		{
			\MySQL::getDB()->delete("mapknownwormholes", array("id" => $this->id));
		}

		function getColor()
		{
			if ($this->status > 1)
				return "#4499FF";
			if ($this->status == 1)
				return "#88AAFF";
			if ($this->status == 0)
				return "none";
			if ($this->status == -1)
				return "#FF5555";
			if ($this->status < -1)
				return "#CC0000";
		}

		function getStatus()
		{
			if ($this->status > 1)
				return "Alliance System";
			if ($this->status == 1)
				return "Friendly System";
			if ($this->status == 0)
				return "Neutral System";
			if ($this->status == -1)
				return "Hostile System";
			if ($this->status < -1)
				return "Dangerous System";
		}

		function getIcon()
		{
			if ($this->status > 1)
				return "images/eve/standing.alliance.png";
			if ($this->status == 1)
				return "images/eve/standing.blue.png";
			if ($this->status == 0)
				return "images/eve/standing.neutral.png";
			if ($this->status == -1)
				return "images/eve/standing.red.png";
			if ($this->status < -1)
				return "images/eve/standing.war.png";
		}

		/**
		 * Get solarsystem
		 * @return \scanning\model\System
		 */
		function getSolarSystem()
		{
			if ($this->solarsystem === null)
				$this->solarsystem = new \scanning\model\System($this->systemID);

			return $this->solarsystem;

		}
	}
}
?>