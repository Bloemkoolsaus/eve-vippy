<?php
namespace scanning\model
{
	class Connection
	{
		public $id = 0;
		public $fromWormholeID = 0;
		public $toWormholeID = 0;
		public $chainID = 0;
		public $eol = false;
		public $lifetimeUpdateDate = null;
		public $lifetimeUpdateBy = null;
		public $mass = 0; // 0 = vol mass. 1 = reduced. 2 = crit.
		public $massUpdateDate = null;
		public $massUpdateBy = null;
		public $normalgates = false;
		public $frigateHole = false;
		public $allowCapitals = false;
		public $fromWHTypeID = 0;
		public $toWHTypeID = 0;
		public $kspaceJumps = 0;
		public $addDate;
		public $addBy = 0;
		public $updateDate;
		public $updateBy = 0;

		private $fromSystem = null;
		private $toSystem = null;
		private $fromWormhole = null;
		private $toWormhole = null;
		private $addedByUser = null;
		private $updateByUser = null;
		private $lifetimeUpdateUser = null;
		private $massUpdateUser = null;
		private $chain = null;
		private $fromWormholeType = null;
		private $toWormholeType = null;

		function __construct($id=false)
		{
			if ($id)
				$this->loadById($id);
		}

		function loadById($id)
		{
			if ($result = \MySQL::getDB()->getRow("SELECT * FROM mapwormholeconnections WHERE id = ?", array($id)))
				$this->load($result);
		}

		function getResultset()
		{
			if ($result = \MySQL::getDB()->getRow("	SELECT 	*
													FROM 	mapwormholeconnections
													WHERE 	((fromwormholeid = ? AND towormholeid = ?)
													OR 		(fromwormholeid = ? AND towormholeid = ?))
													AND		chainid = ?"
						, array($this->fromWormholeID, $this->toWormholeID,
								$this->toWormholeID, $this->fromWormholeID,
								$this->chainID)))
			{
				return $result;
			}

			return false;
		}

		function load($result=false)
		{
			if (!$result)
				$result = $this->getResultset();

			if ($result)
			{
				$this->id = $result["id"];
				$this->chainID = $result["chainid"];
				$this->fromWormholeID = $result["fromwormholeid"];
				$this->toWormholeID = $result["towormholeid"];
				$this->eol = ($result["eol"]>0)?true:false;
				$this->lifetimeUpdateBy = $result["lifetimeupdateby"];
				$this->lifetimeUpdateDate = $result["lifetimeupdatedate"];
				$this->mass = $result["mass"];
				$this->massUpdateBy = $result["massupdateby"];
				$this->massUpdateDate = $result["massupdatedate"];
				$this->normalgates = ($result["normalgates"]>0)?true:false;
				$this->frigateHole = ($result["frigatehole"]>0)?true:false;
				$this->allowCapitals = ($result["allowcapitals"]>0)?true:false;
				$this->fromWHTypeID = $result["fromwhtypeid"];
				$this->toWHTypeID = $result["towhtypeid"];
				$this->kspaceJumps = $result["kspacejumps"];
				$this->addDate = $result["adddate"];
				$this->addBy = $result["addedby"];
				$this->updateDate = $result["updatedate"];
				$this->updateBy = $result["updateby"];
			}
		}

		function exists()
		{
			if ($result = $this->getResultset())
				return true;
			else
				return false;
		}

		function store($doCopy=true)
		{
			// Kopie's even uit. Dit gaat niet helemaal goed...
			$doCopy = false;

			// Is er iets gewijzigd?
			$oldConnection = new \scanning\model\Connection();
			if ($this->id > 0)
				$oldConnection->loadById($this->id);

			// Mass gewijzigd
			if ($this->mass > 0 && $oldConnection->mass !== $this->mass) {
				$this->massUpdateBy = \User::getUSER()->id;
				$this->massUpdateDate = date("Y-m-d H:i:s");
			}

			// Lifetime gewijzigd
			if ($oldConnection->eol !== $this->eol) {
				$this->lifetimeUpdateBy = \User::getUSER()->id;
				$this->lifetimeUpdateDate = date("Y-m-d H:i:s");
			}


            // Probeer wh-types te achterhalen.
            $wormholeFrom = $this->getFromWormhole();
            $wormholeTo = $this->getToWormhole();
            if ($wormholeFrom && $wormholeTo)
            {
                $signatureFrom = \map\model\Signature::findWormholeSigByName($wormholeFrom->solarSystemID, $wormholeFrom->getChain()->authgroupID, $wormholeFrom->name."-".$wormholeTo->name);
                if ($signatureFrom)
                    $this->fromWHTypeID = $signatureFrom->whTypeID;

                $signatureTo = \map\model\Signature::findWormholeSigByName($wormholeTo->solarSystemID, $wormholeFrom->getChain()->authgroupID, $wormholeTo->name."-".$wormholeFrom->name);
                if ($signatureTo)
                    $this->toWHTypeID = $signatureTo->whTypeID;
            }

            // WH Type bekend? Probeer te achterhalen of dit een frigate / capital hole is.
			if ($this->fromWHTypeID || $this->toWHTypeID)
			{
                if (!$this->frigateHole)
				    $this->frigateHole = false;

				$this->allowCapitals = false;

				$fromType = new \scanning\model\WormholeType($this->fromWHTypeID);
				$toType = new \scanning\model\WormholeType($this->toWHTypeID);

				$wormholeType = ($fromType->isK162()) ? $toType : $fromType;
				if (!$wormholeType->isK162())
				{
					// Frigate hole?
					if (($wormholeType->jumpmass < 10) ||
						($this->getFromSystem() != null && $this->getFromSystem()->isFrigateOnly()) ||
						($this->getToSystem() != null && $this->getToSystem()->isFrigateOnly()))
					{
						$this->frigateHole = true;
						$this->allowCapitals = false;
					}

					// Capital capable?
					if (!$this->frigateHole)
					{
						if (($this->getFromSystem() !== null && $this->getFromSystem()->isCapitalCapable()) &&
							($this->getToSystem() !== null && $this->getToSystem()->isCapitalCapable()))
						{
							if ($wormholeType->jumpmass > 1000)
								$this->allowCapitals = true;
						}
					}
				}
			}
			else
			{
				// WH Type niet bekend. Gokken!
				if ($this->frigateHole ||
					(($this->getFromSystem() != null && $this->getFromSystem()->isFrigateOnly()) ||
					($this->getToSystem() != null && $this->getToSystem()->isFrigateOnly())))
				{
					$this->frigateHole = true;
					$this->allowCapitals = false;
					$data["allowcapitals"] = 0;
				}
				else
				{
					// Capital capable?
					if (($this->getFromSystem() !== null && $this->getFromSystem()->isCapitalCapable()) &&
						($this->getToSystem() !== null && $this->getToSystem()->isCapitalCapable()))
					{
						$this->allowCapitals = true;
						$data["allowcapitals"] = 1;
					}
				}
			}

			if ($doCopy)
			{
				if ($this->getFromWormhole()->getSolarsystem() == null)
					$doCopy = false;
				else if ($this->getToWormhole()->getSolarsystem() == null)
					$doCopy = false;
			}

			$data = array(	"chainid"		=> $this->chainID,
							"fromwormholeid"=> $this->fromWormholeID,
							"towormholeid"	=> $this->toWormholeID,
							"eol"			=> ($this->eol)?1:0,
							"lifetimeupdateby"	=> $this->lifetimeUpdateBy,
							"lifetimeupdatedate"=> $this->lifetimeUpdateDate,
							"mass"			=> $this->mass,
							"massupdateby"	=> $this->massUpdateBy,
							"massupdatedate"=> $this->massUpdateDate,
							"normalgates"	=> ($this->normalgates)?1:0,
							"frigatehole"	=> ($this->frigateHole)?1:0,
							"allowcapitals"	=> ($this->allowCapitals)?1:0,
							"fromwhtypeid"	=> $this->fromWHTypeID,
							"towhtypeid"	=> $this->toWHTypeID,
							"kspacejumps"	=> $this->kspaceJumps,
							"updatedate"	=> date("Y-m-d H:i:s"),
							"updateby"		=> \User::getUSER()->id);

			if ($this->exists())
			{
				/**
				 * Bestaat al. Update.
				 */
				$params = array();
				foreach ($data as $var => $val) {
					$params[] = "`".$var."` = ".(($val===null)?"null":"'".\MySQL::escape($val)."'");
				}

				\MySQL::getDB()->doQuery("	UPDATE 	mapwormholeconnections
											SET 	".implode(", ", $params)."
											WHERE 	((fromwormholeid = ? AND towormholeid = ?)
												OR 	(fromwormholeid = ? AND towormholeid = ?))
											AND		chainid = ?"
							, array($this->fromWormholeID, $this->toWormholeID,
									$this->toWormholeID, $this->fromWormholeID,
									$this->chainID));
			}
			else
			{
				/**
				 * Insert een nieuwe.
				 */
				// kspace to kspace?
				if (($this->getFromSystem() !== null && $this->getFromSystem()->isKSpace()) &&
					($this->getToSystem() !== null && $this->getToSystem()->isKSpace()))
				{
					$this->kspaceJumps = $this->getFromSystem()->getNrJumpsTo($this->getToSystem()->id);
					if ($this->kspaceJumps <= 1)
						$this->normalgates = 1;

					$data["kspacejumps"] = $this->kspaceJumps;
					$data["normalgates"] = $this->normalgates;
				}

				$this->addDate = date("Y-m-d H:i:s");
				$this->addBy = \User::getUSER()->id;
				$data["adddate"] = $this->addDate;
				$data["addedby"] = $this->addBy;

				if ($this->id == 0)
				{
					$result = \MySQL::getDB()->insert("mapwormholeconnections", $data);
					$this->id = $result;
				}
			}

			// Update chain cache timer
			$this->getChain()->setMapUpdateDate();


			// Check the same connection on other maps.
			if ($doCopy)
			{
				foreach (\scanning\model\Connection::getConnectionByLocationsAuthGroup(
														$this->getFromWormhole()->solarSystemID,
														$this->getToWormhole()->solarSystemID,
														$this->getChain()->authgroupID) as $connection)
				{
					if ($connection->id !== $this->id)
					{
						foreach (get_object_vars($connection) as $var => $val)
						{
							if (!in_array($var, array("id","chainID","fromWormholeID","toWormholeID")))
								$connection->$var = $this->$var;
						}
						$connection->store(false);
					}
				}
			}
		}

		function getAgeInHours()
		{
			return (strtotime("now") - strtotime($this->addDate))/3600;
		}

		function delete()
		{
            if (\User::getUSER()->isAllowedChainAction($this->getChain(), "delete"))
            {
                \MySQL::getDB()->delete("mapwormholeconnections", array("id" => $this->id));

                // Update chain cache timer
                $this->getChain()->setMapUpdateDate();

                // Check the same connection on other maps.
                foreach (\scanning\model\Connection::getConnectionByLocationsAuthGroup(
                                $this->getFromWormhole()->solarSystemID,
                                $this->getToWormhole()->solarSystemID,
                                $this->getChain()->authgroupID) as $connection)
                {
                    if ($connection->id !== $this->id)
                        $connection->delete();
                }
            }
		}

        /**
         * Add jump
         * @param integer $shiptypeID
         * @param integer $pilotID |null
         * @param bool    $copy
         */
		function addJump($shiptypeID, $pilotID=null, $copy=true)
		{
            \AppRoot::debug("addJump($shiptypeID, $pilotID, $copy)");

            $ship = new \eve\model\Ship($shiptypeID);
			\MySQL::getDB()->insert("mapwormholejumplog",
								array(	"connectionid"	=> $this->id,
										"chainid"		=> $this->getChain()->id,
										"fromsystemid"	=> $this->getFromSystem()->id,
										"tosystemid"	=> $this->getToSystem()->id,
										"characterid"	=> $pilotID,
										"shipid"		=> $ship->id,
                                        "mass"          => $ship->mass,
										"jumptime"		=> date("Y-m-d H:i:s")));

			// Check the same connection on other maps.
			if ($copy)
			{
				foreach (\scanning\model\Connection::getConnectionByLocationsAuthGroup(
														$this->getFromWormhole()->solarSystemID,
														$this->getToWormhole()->solarSystemID,
														$this->getChain()->authgroupID) as $connection)
				{
					if ($connection->id !== $this->id)
						$connection->addJump($shiptypeID, $pilotID, false);
				}
			}
		}

        function addMass($amount, $copy=true)
        {
            $data = array("connectionid"=> $this->id,
                          "chainid"		=> $this->getChain()->id,
                          "fromsystemid"=> $this->getFromSystem()->id,
                          "tosystemid"	=> $this->getToSystem()->id,
                          "characterid"	=> null,
                          "shipid"		=> null,
                          "mass"        => $amount,
                          "jumptime"	=> date("Y-m-d H:i:s"));
            \MySQL::getDB()->insert("mapwormholejumplog", $data);
            \User::getUSER()->addLog("addmass", $this->id, $data);

            // Check the same connection on other maps.
            if ($copy)
            {
                foreach (\scanning\model\Connection::getConnectionByLocationsAuthGroup(
                                                        $this->getFromWormhole()->solarSystemID,
                                                        $this->getToWormhole()->solarSystemID,
                                                        $this->getChain()->authgroupID) as $connection)
                {
                    if ($connection->id !== $this->id)
                        $connection->addMass($amount, false);
                }
            }
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
		 * Get from wormhole
		 * @return \scanning\model\Wormhole
		 */
		function getFromWormhole()
		{
			if ($this->fromWormhole == null)
				$this->fromWormhole = new \scanning\model\Wormhole($this->fromWormholeID);

			return $this->fromWormhole;
		}

		/**
		 * Get to wormhole
		 * @return \scanning\model\Wormhole
		 */
		function getToWormhole()
		{
			if ($this->toWormhole == null)
				$this->toWormhole = new \scanning\model\Wormhole($this->toWormholeID);

			return $this->toWormhole;
		}


		/**
		 * Get from solarsystem
		 * @return \eve\model\Solarsystem|null
		 */
		function getFromSystem()
		{
			if ($this->fromSystem == null)
			{
				if ($this->getFromWormhole()->solarSystemID > 0)
					$this->fromSystem = new \eve\model\SolarSystem($this->getFromWormhole()->solarSystemID);
			}

			return $this->fromSystem;
		}

		/**
		 * Get to solarsystem
		 * @return \eve\model\Solarsystem|null
		 */
		function getToSystem()
		{
			if ($this->toSystem == null)
			{
				if ($this->getToWormhole()->solarSystemID > 0)
					$this->toSystem = new \eve\model\SolarSystem($this->getToWormhole()->solarSystemID);
			}

			return $this->toSystem;
		}


		/**
		 * Get added by user
		 * @return \users\model\User
		 */
		function getAddedByUser()
		{
			if ($this->addedByUser == null)
				$this->addedByUser = new \users\model\User($this->addBy);

			return $this->addedByUser;
		}

		/**
		 * Get added by user
		 * @return \users\model\User
		 */
		function getUpdatedByUser()
		{
			if ($this->updateByUser == null)
				$this->updateByUser = new \users\model\User($this->updateBy);

			return $this->updateByUser;
		}

		/**
		 * Get added by user
		 * @return \users\model\User
		 */
		function getLifetimeUpdateUser()
		{
			if ($this->lifetimeUpdateUser == null)
				$this->lifetimeUpdateUser = new \users\model\User($this->updateBy);

			return $this->lifetimeUpdateUser;
		}

		/**
		 * Get added by user
		 * @return \users\model\User
		 */
		function getMassUpdateUser()
		{
			if ($this->massUpdateUser == null)
				$this->massUpdateUser = new \users\model\User($this->updateBy);

			return $this->massUpdateUser;
		}

		/**
		 * Get from wormhole type
		 * @return \scanning\model\WormholeType
		 */
		function getFromWormholeType()
		{
			if ($this->fromWormholeType == null)
				$this->fromWormholeType = new \scanning\model\WormholeType($this->fromWHTypeID);

			return $this->fromWormholeType;
		}

		/**
		 * Get to wormhole type
		 * @return \scanning\model\WormholeType
		 */
		function getToWormholeType()
		{
			if ($this->toWormholeType == null)
				$this->toWormholeType = new \scanning\model\WormholeType($this->toWHTypeID);

			return $this->toWormholeType;
		}

		/**
		 * Get wormhole type
		 * @return \scanning\model\WormholeType
		 */
		function getWormholeType()
		{
			if (!$this->getFromWormholeType()->isK162())
				return $this->getFromWormholeType();
			else
				return $this->getToWormholeType();
		}

        /**
         * Is kspace to kspace
         * @return bool
         */
        function isKspaceToKspace()
        {
            if ($this->getFromSystem()->isKSpace())
            {
                if ($this->getToSystem()->isKSpace())
                    return true;
            }
            return false;
        }






		/**
		 * Get connection
		 * @param integer $systemID
		 * @param integer $chainID
		 * @return \scanning\model\Connection[]
		 */
		public static function getConnectionBySystem($systemID, $chainID)
		{
			$connections = array();
			if ($results = \MySQL::getDB()->getRows("SELECT	c.*
													FROM	mapwormholeconnections c
														INNER JOIN mapwormholes wf ON wf.chainid = c.chainid AND c.fromwormholeid = wf.id
														INNER JOIN mapwormholes wt ON wt.chainid = c.chainid AND c.towormholeid = wt.id
													WHERE	(wf.solarsystemid = ? OR wt.solarsystemid = ?)
													AND		c.chainid = ?
													GROUP BY c.id"
								, array($systemID, $systemID, $chainID)))
			{
				foreach ($results as $result)
				{
					$conn = new \scanning\model\Connection();
					$conn->load($result);
					$connections[] = $conn;
				}
			}
			return $connections;
		}

		/**
		 * Get connected system. Sorted by add-date.
		 * @param integer $systemID
		 * @param integer $chainID
		 * @return \scanning\model\Wormhole[]
		 */
		public static function getConnectedSystems($systemID, $chainID)
		{
			$systems = array();

			if ($results = \MySQL::getDB()->getRows("SELECT	wf.*
													FROM 	mapwormholeconnections c
														LEFT JOIN mapwormholes wf ON wf.chainid = c.chainid AND c.fromwormholeid = wf.id
														LEFT JOIN mapwormholes wt ON wt.chainid = c.chainid AND c.towormholeid = wt.id
													WHERE 	(wf.solarsystemid = ? OR wt.solarsystemid = ?)
													AND		c.chainid = ?
												UNION
													SELECT	wt.*
													FROM 	mapwormholeconnections c
														LEFT JOIN mapwormholes wf ON wf.chainid = c.chainid AND c.fromwormholeid = wf.id
														LEFT JOIN mapwormholes wt ON wt.chainid = c.chainid AND c.towormholeid = wt.id
													WHERE 	(wf.solarsystemid = ? OR wt.solarsystemid = ?)
													AND		c.chainid = ?
												ORDER BY adddate ASC"
								, array($systemID, $systemID, $chainID,
										$systemID, $systemID, $chainID)))
			{
				foreach ($results as $result)
				{
					$system = new \scanning\model\Wormhole();
					$system->load($result);

					if ($system->solarSystemID !== $systemID)
						$systems[] = $system;
				}
			}

			return $systems;
		}

		/**
		 * Get connection
		 * @param integer $fromSystemID
		 * @param integer $toSystemID
		 * @param integer $chainID
		 * @return \scanning\model\Connection|NULL
		 */
		public static function getConnectionByLocations($fromSystemID, $toSystemID, $chainID)
		{
			if ($result = \MySQL::getDB()->getRow("	SELECT	c.*
													FROM	mapwormholeconnections c
														INNER JOIN mapwormholes wf ON wf.chainid = c.chainid AND c.fromwormholeid = wf.id
														INNER JOIN mapwormholes wt ON wt.chainid = c.chainid AND c.towormholeid = wt.id
													WHERE	((wf.solarsystemid = ? AND wt.solarsystemid = ?)
														OR	(wf.solarsystemid = ? AND wt.solarsystemid = ?))
													AND		c.chainid = ?
													GROUP BY c.id"
										, array($fromSystemID, $toSystemID,
												$toSystemID, $fromSystemID,
												$chainID)))
			{
				$connection = new \scanning\model\Connection();
				$connection->load($result);
				return $connection;
			}
			return null;
		}

		/**
		 * Get connection
		 * @param integer $fromSystemID
		 * @param integer $toSystemID
		 * @param integer $authGroupID
		 * @return \scanning\model\Connection[]
		 */
		public static function getConnectionByLocationsAuthGroup($fromSystemID, $toSystemID, $authGroupID)
		{
			$connections = array();
			if ($results = \MySQL::getDB()->getRows("SELECT	c.*
													FROM	mapwormholeconnections c
														INNER JOIN mapwormholechains ch ON ch.id = c.chainid
														INNER JOIN mapwormholes wf ON wf.chainid = c.chainid AND c.fromwormholeid = wf.id
														INNER JOIN mapwormholes wt ON wt.chainid = c.chainid AND c.towormholeid = wt.id
													WHERE	((wf.solarsystemid = ? AND wt.solarsystemid = ?)
														OR	(wf.solarsystemid = ? AND wt.solarsystemid = ?))
													AND		ch.authgroupid = ?
													GROUP BY c.id"
										, array($fromSystemID, $toSystemID,
												$toSystemID, $fromSystemID,
												$authGroupID)))
			{
				foreach ($results as $result)
				{
					$connection = new \scanning\model\Connection();
					$connection->load($result);
					$connections[] = $connection;
				}
			}

			return $connections;
		}

		/**
		 * Get connection
		 * @param integer $fromWhID
		 * @param integer $toWhID
		 * @param integer $chainID
		 * @return \scanning\model\Connection|NULL
		 */
		public static function getConnectionByWormhole($fromWhID, $toWhID, $chainID)
		{
			if ($result = \MySQL::getDB()->getRow("	SELECT	*
													FROM	mapwormholeconnections
													WHERE	((fromwormholeid = ? AND towormholeid = ?)
														OR	(fromwormholeid = ? AND towormholeid = ?))
													AND		chainid = ?
													GROUP BY id"
										, array($fromWhID, $toWhID, $toWhID, $fromWhID, $chainID)))
			{
				$connection = new \scanning\model\Connection();
				$connection->load($result);
				return $connection;
			}
			return null;
		}
	}
}
?>