<?php
namespace scanning\model
{
	/*
	 *	LET OP!!! Wormhole is het wormhole systeem.
	 *  De daadwerkelijke wormhole (waar je door springt) is Connection
	 */
	class Wormhole
	{
		public $id = 0;
		public $chainID = 0;
		public $signatureID = null;
		public $solarSystemID = 0;
		public $permanent = false;
		public $name = "";
		public $x = 0;
		public $y = 0;
		public $status = 1;
		public $mappedByUserID = 0;
		public $mappedByCharacterID = 0;
		public $addDate = null;
		public $fullScanDate = null;
		public $fullScanDateBy = null;
		public $updateDate;

		private $system = null;
		private $chain = null;
		private $connections = null;
		private $connectedSystems = null;

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
			{
				$cacheFileName = "wormhole/".$this->id.".json";
				if ($cache = \Cache::file()->get($cacheFileName))
					$result = json_decode($cache, true);
				else
				{
					$result = \MySQL::getDB()->getRow("SELECT * FROM mapwormholes WHERE id = ?", array($this->id));
                    \Cache::file()->set($cacheFileName, json_encode($result));
				}
			}

			if ($result)
			{
				$this->id = $result["id"];
				$this->chainID = $result["chainid"];
				$this->signatureID = $result["signatureid"];
				$this->solarSystemID = $result["solarsystemid"];
				$this->permanent = ($result["permanent"]>0)?true:false;
				$this->name = $result["name"];
				$this->x = $result["x"];
				$this->y = $result["y"];
				$this->status = $result["status"];
				$this->mappedByUserID = $result["mappedby_userid"];
				$this->mappedByCharacterID = $result["mappedby_characterid"];
				$this->addDate = $result["adddate"];
				$this->fullScanDate = $result["fullyscanned"];
				$this->fullScanDateBy = $result["fullyscannedby"];
				$this->updateDate = $result["updatedate"];
			}
		}

		function store($positionModifier=null, $copyToOtherChains=true)
		{
			if ($this->addDate == null)
				$this->addDate = date("Y-m-d H:i:s");

			if ($this->mappedByUserID == 0)
				$this->mappedByUserID = (\User::getUSER())?\User::getUSER()->id:null;

			if ($this->mappedByCharacterID == 0) {
				if (\eve\model\IGB::getIGB()->isIGB())
					$this->mappedByCharacterID = \eve\model\IGB::getIGB()->getPilotID();
			}

            if (!$positionModifier)
                $positionModifier = 20;
            $this->x = round($this->x/$positionModifier)*$positionModifier;
            $this->y = round($this->y/$positionModifier)*$positionModifier;

			$data = array(	"chainid"		=> $this->chainID,
							"signatureid"	=> $this->signatureID,
							"solarsystemid" => $this->solarSystemID,
							"permanent"		=> ($this->permanent)?1:0,
							"name"			=> $this->name,
							"x"				=> $this->x,
							"y"				=> $this->y,
							"status"		=> $this->status,
							"mappedby_userid"		=> $this->mappedByUserID,
							"mappedby_characterid"	=> $this->mappedByCharacterID,
							"fullyscanned"	=> $this->fullScanDate,
							"fullyscannedby"=> $this->fullScanDateBy,
							"adddate"		=> date("Y-m-d H:i:s", strtotime($this->addDate)),
							"updatedate"	=> date("Y-m-d H:i:s"));
			if ($this->id != 0)
				$data["id"] = $this->id;

            // New wormhole
			if (!$this->id)
			{
                $this->id = \MySQL::getDB()->insert("mapwormholes", $data);

                // User log
                if (\User::getUSER())
                {
                    \User::getUSER()->addLog("add-wormhole", $this->solarSystemID, [
                        "system" => [
                            "id" => $this->solarSystemID,
                            "name" => ($this->getSolarsystem() !== null) ? $this->getSolarsystem()->name : null
                        ],
                        "chain" => [
                            "id" => $this->getChain()->id,
                            "name" => $this->getChain()->name
                        ]
                    ]);
                }
			}
			else
				$result = \MySQL::getDB()->update("mapwormholes", $data, array("id" => $this->id));


            // Check en update connections
            foreach ($this->getConnections() as $connection) {
                $connection->store();
            }

            // Remove cache so that it resets
            \Cache::file()->remove("wormhole/".$this->id.".json");
            $this->getChain()->setMapUpdateDate();
		}

		function delete()
		{
            if (\AppRoot::isCommandline() || \User::getUSER()->isAllowedChainAction($this->getChain(), "delete"))
			    $this->getChain()->removeWormhole($this);
		}

		function move($newX, $newY, $modifier=null)
		{
            if (\AppRoot::isCommandline() || \User::getUSER()->isAllowedChainAction($this->getChain(), "move"))
            {
                $this->x = $newX;
                $this->y = $newY;
                $this->store($modifier);

                if (\User::getUSER()) {
                    \User::getUSER()->addLog("move-wormhole", $this->solarSystemID, [
                        "delete-all" => false,
                        "wormhole"   => ["id" => $this->id, "name" => $this->name],
                        "system"     => ["id" => ($this->getSolarsystem())?$this->getSolarsystem()->id:0, "name" => (($this->getSolarsystem())?$this->getSolarsystem()->name." - ":"").$this->name],
                        "chain"      => ["id" => $this->getChain()->id, "name" => $this->getChain()->name]
                    ]);
                }
            }
		}

		/**
		 * Get solarsystem
		 * @return \scanning\model\System|null
		 */
		function getSolarsystem()
		{
			if ($this->system == null && $this->solarSystemID > 0)
				$this->system = new \scanning\model\System($this->solarSystemID);

			return $this->system;
		}

		function isReservation()
		{
			if ($this->solarSystemID == 0)
				return true;

			return false;
		}

		/**
		 * Get chain
		 * @return \scanning\model\Chain
		 */
		function getChain()
		{
			if ($this->chain == null)
				$this->chain = new \scanning\model\Chain($this->chainID);

			return $this->chain;
		}

		/**
		 * Is this the home system
		 * @return boolean
		 */
		function isHomeSystem()
		{
			if ($this->getChain()->homesystemID == $this->solarSystemID)
				return true;

			return false;
		}

        function isPermenant()
        {
            if ($this->permanent)
                return true;
            if ($this->isHomeSystem())
                return true;

            return false;
        }

		function showContextMenu()
		{
		}

		/**
		 * Get connections
		 * @return \scanning\model\Connection[]
		 */
		function getConnections()
		{
			if ($this->connections === null)
				$this->connections = \scanning\model\Connection::getConnectionBySystem($this->solarSystemID, $this->chainID);

			return $this->connections;
		}

		/**
		 * Get connection to system
		 * @param integer $solarSystemID
		 * @return \scanning\model\Connection|null
		 */
		function getConnectionTo($solarSystemID)
		{
			foreach ($this->getConnections() as $connection) {
				if ($connection->getFromWormhole() && $connection->getFromWormhole()->id == $this->id) {
					if ($connection->getToSystem() && $connection->getToSystem()->id == $solarSystemID)
						return $connection;
				} else {
					if ($connection->getFromSystem() && $connection->getFromSystem()->id == $solarSystemID)
						return $connection;
				}
			}

			return null;
		}

		/**
		 * Get connected systems
		 * @return \scanning\model\Wormhole[]
		 */
		function getConnectedSystems()
		{
			if ($this->connectedSystems === null)
				$this->connectedSystems = \scanning\model\Connection::getConnectedSystems($this->solarSystemID, $this->chainID);

			return $this->connectedSystems;
		}

		/**
		 * Connected?
		 * @param integer $systemID
		 * @return boolean
		 */
		function isConnectedTo($systemID)
		{
			\AppRoot::debug("is connected to: ".$systemID);
			foreach ($this->getConnections() as $connection)
			{
				if ($connection->getFromWormhole()->solarSystemID == $this->solarSystemID && $connection->getToSystem()->id == $systemID)
					return true;

				if ($connection->getToWormhole()->solarSystemID == $this->solarSystemID && $connection->getFromSystem()->id == $systemID)
					return true;
			}

			return false;
		}

		/**
		 * get full scanned by user
		 * @return \users\model\User|null
		 */
		function getFullyScannedByUser()
		{
			if ($this->fullScanDateBy != null && $this->fullScanDateBy > 0)
				return new \users\model\User($this->fullScanDateBy);

			return null;
		}

		function markFullyScanned()
		{
			$this->fullScanDate = date("Y-m-d H:i:s");
			$this->fullScanDateBy = \User::getUSER()->id;
			$this->store();
			$this->getChain()->setMapUpdateDate();
		}


		/**
		 * Get route to selected system
		 * @param \scanning\model\Wormhole $toSystem
		 * @return \scanning\model\Wormhole[]
		 */
		function getRouteToSystem(\scanning\model\Wormhole $toSystem=null)
		{
			\AppRoot::debug("getRouteToSystem()");
			if ($toSystem == null)
				$toSystem = self::getWormholeBySystemID($this->getChain()->getHomeSystem()->id, $this->chainID);

			$routes = array();
			foreach ($this->getChain()->getWormholes() as $wormhole)
			{
				foreach ($wormhole->getConnections() as $connection)
				{
                    if ($connection->getFromSystem() == null || $connection->getToSystem() == null)
                        continue;

					$routes[$connection->getFromSystem()->id][$connection->getToSystem()->id] = 1;
					$routes[$connection->getToSystem()->id][$connection->getFromSystem()->id] = 1;
				}
			}

            $routeSystems = \Tools::getDijkstraRoute($this->getSolarsystem()->id, $toSystem->getSolarsystem()->id, $routes);
            \AppRoot::debug("<pre>" . print_r($routeSystems, true) . "</pre>");

            return $routeSystems;
		}



		/**
		 * Get wormhole system
		 * @param int $solarSystemID
		 * @param int $chainID
		 * @return \scanning\model\Wormhole|null
		 */
		public static function getWormholeBySystemID($solarSystemID, $chainID=null)
		{
            \AppRoot::debug("getWormholeBySystemID($solarSystemID, $chainID)");
            if (!$chainID)
                $chainID = \Tools::REQUEST("chainid");

            if ($chainID)
            {
                if ($result = \MySQL::getDB()->getRow("select * from mapwormholes where solarsystemid = ? AND chainid = ?", [$solarSystemID, $chainID])) {
                    $system = new \scanning\model\Wormhole();
                    $system->load($result);
                    return $system;
                }
            }

			return null;
		}

		/**
		 * Get wormhole system
		 * @param string $name
		 * @param int $chainID
		 * @return \scanning\model\Wormhole|null
		 */
		public static function getWormholeBySystemByName($name, $chainID=null)
		{
			if ($chainID == null)
				$chainID = \scanning\model\Chain::getCurrentChain()->id;

			if ($result = \MySQL::getDB()->getRow("SELECT * FROM mapwormholes WHERE name = ? AND chainid = ?", [$name, $chainID]))
			{
				$system = new \scanning\model\Wormhole();
				$system->load($result);
				return $system;
			}

			return null;
		}

		/**
		 * Get systems by chain
		 * @param int $chainID
		 * @return \scanning\model\Wormhole[]
		 */
		public static function getWormholesByChain($chainID)
		{
			$wormholes = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM mapwormholes WHERE chainid = ?", array($chainID)))
			{
				foreach ($results as $result)
				{
					$system = new \scanning\model\Wormhole();
					$system->load($result);
					$wormholes[] = $system;
				}
			}
			return $wormholes;
		}

		/**
		 * Get systems by solarsystem
		 * @param int $solarSystemID
		 * @return \scanning\model\Wormhole[]
		 */
		public static function getWormholesBySolarsystemID($solarSystemID)
		{
			$wormholes = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM mapwormholes WHERE solarsystemid = ?", array($solarSystemID)))
			{
				foreach ($results as $result)
				{
					$system = new \scanning\model\Wormhole();
					$system->load($result);
					$wormholes[] = $system;
				}
			}
			return $wormholes;
		}


		/**
		 * Get systems by chain
		 * @param int $authGroupID authgroup-id
		 * @param int $systemID solarsystem-id
		 * @return \scanning\model\Wormhole[]
		 */
		public static function getWormholesByAuthgroup($authGroupID, $systemID=null)
		{
			$qurey = array();
			$query[] = "c.deleted = 0";

			if (is_numeric($authGroupID))
				$query[] = "c.authgroupid = ".$authGroupID;
			else
				return array();

			if ($systemID != null && is_numeric($systemID))
				$query[] = "w.solarsystemid = ".$systemID;


			$wormholes = array();
			if ($results = \MySQL::getDB()->getRows("SELECT w.*
													FROM 	mapwormholes w
														INNER JOIN mapwormholechains c ON c.id = w.chainid
													WHERE 	".implode(" AND ", $query)))
			{
				foreach ($results as $result)
				{
					$system = new \scanning\model\Wormhole();
					$system->load($result);
					$wormholes[] = $system;
				}
			}
			return $wormholes;
		}

		/**
		 * Get wormhole by chain/signature
		 * @param integer $chainID
		 * @param integer $signatureID
		 * @return \scanning\model\Wormhole|NULL
		 */
		public static function getWormholeBySignatureID($chainID, $signatureID)
		{
			if ($result = \MySQL::getDB()->getRow("SELECT * FROM mapwormholes WHERE chainid = ? AND signatureid = ?"
											, array($chainID, $signatureID)))
			{
				$system = new \scanning\model\Wormhole();
				$system->load($result);
				return $system;
			}

			return null;
		}
	}
}
?>