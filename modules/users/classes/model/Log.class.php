<?php
namespace users\model
{
	class Log
	{
		public $id;
		public $userID;
		public $pilotID;
		public $logDate;
		public $lastDate;
		public $what;
		public $whatID;
		public $ipAddress;
		public $sessionID;
		public $userAgent;
		public $extraInfo;

		private $user = null;
		private $character = null;

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
				$result = \MySQL::getDB()->getRows("SELECT * FROM user_log WHERE id = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["id"];
				$this->userID = $result["userid"];
				$this->pilotID = $result["pilotid"];
				$this->logDate = $result["logdate"];
				$this->lastDate = $result["lastdate"];
				$this->what = $result["what"];
				$this->whatID = $result["whatid"];
				$this->ipAddress = $result["ipaddress"];
				$this->sessionID = $result["sessionid"];
				$this->userAgent = $result["useragent"];
				$this->extraInfo = $result["extrainfo"];
			}
		}

		function store()
		{
			if ($this->logDate == null)
			{
				$this->logDate = date("Y-m-d H:i:s");
				$this->lastDate = date("Y-m-d H:i:s");
			}

			$data = array("userid"	=> $this->userID,
						"pilotid"	=> $this->pilotID,
						"lastdate"	=> $this->lastDate,
						"logdate"	=> $this->logDate,
						"what"		=> $this->what,
					    "whatid"    => $this->whatID,
						"ipaddress"	=> $this->ipAddress,
						"sessionid"	=> $this->sessionID,
						"useragent"	=> $this->userAgent,
					    "extrainfo" => $this->extraInfo);
			if ($this->id == 0)
				$data["id"] = $this->id;

			\MySQL::getDB()->updateinsert("user_log", $data, array("id" => $this->id));
		}

		/**
		 * Get user
		 * @return \users\model\User
		 */
		function getUser()
		{
			if ($this->user == null)
				$this->user = new \users\model\User($this->userID);

			return $this->user;
		}

		/**
		 * Get pilot/character
		 * @return \eve\model\Character|null
		 */
		function getPilot()
		{
			if ($this->character === null && $this->pilotID > 0)
				$this->character = new \eve\model\Character($this->pilotID);

			return $this->character;
		}

		/**
		 * Get action
		 * @return string
		 */
		function getAction()
		{
			switch ($this->what)
			{
				case "login":
					return "Login";
				case "add-wormhole":
					return "Add wormhole";
				case "delete-wormhole":
					$data = json_decode($this->extraInfo,true);
					if (isset($data["delete-all"])) {
						if ($data["delete-all"] == true)
							return "Delete ALL";
					}
					return "Delete Wormhole";
				case "apikey-owner-changed":
					return "APIkey changed owner";
				case "character-owner-changed":
					return "Pilot changed owner";
				case "character-apikey-changed":
					return "Pilot API changed";
				case "login-unowned-character":
					return "Pilot mismatch";
			}

			return $this->what;
		}

		function getLevel()
		{
			$levels = array();
			$levels["critical"] = array("apikey-owner-changed","character-owner-changed","login-unowned-character");
			$levels["notice"] = array("character-apikey-changed");

			foreach ($levels as $level => $actions)
			{
				foreach ($actions as $action) {
					if ($action == $this->what)
						return $level;
				}
			}

			return "normal";
		}

		function getIcon()
		{
			switch ($this->getLevel())
			{
				case "critical":
					return "images/default/alert.png";
				case "notice":
					return "images/default/info.png";
			}

			return null;
		}

		/**
		 * Get details
		 * @return string
		 */
		function getDescription()
		{
			$description = "";

			$pilot = null;
			if ($this->pilotID !== null && $this->pilotID != 0)
				$pilot = new \eve\model\Character($this->pilotID);

			if ($pilot !== null && strlen(trim($pilot->name)) > 0)
				$description .= "<div><b>Pilot:</b> ".$pilot->name."</div>";

			if ($this->what == "login-unowned-character")
				$description .= "<div style='color:red;'>Pilot is not owned by this user!</div>";

			$data = json_decode($this->extraInfo,true);

			if (isset($data["chain"]) && strlen(trim($data["chain"]["name"])) > 0)
				$description .= "<div><b>Map:</b> ".$data["chain"]["name"]."</div>";

			if (isset($data["system"]) && strlen(trim($data["system"]["name"])) > 0)
				$description .= "<div><b>System:</b> ".$data["system"]["name"]."</div>";

			if (isset($data["character"]))
			{
				if ($pilot == null || $pilot->id != $data["character"])
				{
					$character = new \eve\model\Character($data["character"]);
					if (strlen(trim($character->name)) > 0)
						$description .= "<div><b>Pilot:</b> ".$character->name."</div>";
				}
			}

			if (isset($data["fromuser"]))
			{
				$fromuser = new \users\model\User($data["fromuser"]);
				$description .= "<div><b>From User:</b> ".$fromuser->getFullName()."</div>";
			}
			if (isset($data["touser"]))
			{
				$touser = new \users\model\User($data["touser"]);
				$description .= "<div><b>To User:</b> ".$touser->getFullName()."</div>";
			}

			if (isset($data["apikey"]))
				$description .= "<div><b>API-Key:</b> ".$data["apikey"]."</div>";

			if (isset($data["fromapi"]))
				$description .= "<div><b>From API-Key:</b> ".$data["fromapi"]."</div>";

			if (isset($data["toapi"]))
				$description .= "<div><b>To API-Key:</b> ".$data["toapi"]."</div>";


			return $description;
		}

		function getIPAddress()
		{
			if ($this->ipAddress == $_SERVER["SERVER_ADDR"])
				return "localhost";

			return $this->ipAddress;
		}

		function getLocation()
		{
			$data = \Tools::getLocationByIP($this->ipAddress);

			if ($data["country_code"] == "xx")
				return null;

			$location = new \stdClass();
			$location->ip = $data["ip"];
			$location->country = new \stdClass();
			$location->country->code = $data["country_code"];
			$location->country->name = ucfirst(strtolower($data["country_name"]));
			$location->city = new \stdClass();
			$location->city->name = ucfirst(strtolower($data["city"]));

			if (!isset($location->country))
				return null;
			if ($location->country->code == "xx")
				return null;

			if (trim($location->city->name) == "(unknown city)" || trim($location->city->name) == "(unknown city?)")
				$location->city->name = "";

			if (trim($location->city->name) == "(private address)" || trim($location->city->name) == "(private address?)")
				$location->city->name = "";
			if (trim($location->country->name) == "(private address)" || trim($location->country->name) == "(private address?)")
				return null;
			if (trim($location->country->name) == "(unknown country)" || trim($location->country->name) == "(unknown country?)")
				return null;

			return $location;
		}




		/**
		 * Get logs by user
		 * @param integer $userID
		 * @return \users\model\Log[]
		 */
		public static function getLogByUser($userID)
		{
			$logs = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM user_log WHERE userid = ? ORDER BY logdate DESC", array($userID)))
			{
				foreach ($results as $result)
				{
					$log = new \users\model\Log();
					$log->load($result);
					$logs[] = $log;
				}
			}
			return $logs;
		}
		/**
		 * Get logs by user
		 * @param integer $userID
		 * @param string $sdate datetime(Y-m-d)
		 * @param string $edate datetime(Y-m-d)
		 * @return \users\model\Log[]
		 */
		public static function getLogByUserOnDate($userID, $sdate, $edate)
		{
			$logs = array();
			if ($results = \MySQL::getDB()->getRows("SELECT *
													FROM 	user_log
													WHERE 	userid = ?
													AND		logdate BETWEEN ? AND ?
													ORDER BY logdate DESC"
					, array($userID, date("Y-m-d",strtotime($sdate))." 00:00:00", date("Y-m-d",strtotime($edate))." 23:59:59")))
			{
				foreach ($results as $result)
				{
					$log = new \users\model\Log();
					$log->load($result);
					$logs[] = $log;
				}
			}
			return $logs;
		}
	}
}
?>