<?php
namespace scanning\model
{
	class Chain
	{
		public $id = 0;
		public $authgroupID = 0;
		public $directorsOnly = false;
		public $homesystemID = 0;
		public $name;
		public $systemName;
		public $prio = 0;
		public $autoNameNewWormholes = true;
		public $deleted = false;
		public $lastActive;

		private $alliances = null;
		private $corporations = null;
		private $homesystem = null;
		private $authgroup = null;
		private $wormholes = null;
		private $allowedUsers = null;

		private static $currentChain = null;

		function __construct($id=false)
		{
			if ($id) {
				$this->id = $id;
				$this->load();
			}
		}

		private function getCacheDirectory()
		{
			return "mapchain/".$this->id."/";
		}

		function resetCache()
		{
			\AppRoot::debug("Chain ".$this->name." (".$this->getHomeSystem()->name.")->resetCache()");
			\Tools::deleteDir($this->getCacheDirectory());
		}

		function load($result=false)
		{
			if (!$result)
			{
				// Check cache
				if (!\AppRoot::config("no-cache-chains"))
				{
					if ($result = \AppRoot::getCache($this->getCacheDirectory()."chaininfo.json"))
						$result = json_decode($result, true);
				}

				if (!$result)
				{
					$result = \MySQL::getDB()->getRow("SELECT * FROM mapwormholechains WHERE id = ?", array($this->id));
					\AppRoot::setCache($this->getCacheDirectory()."chaininfo.json", json_encode($result));
				}
			}

			if ($result)
			{
				$this->id = $result["id"];
				$this->authgroupID = $result["authgroupid"];
				$this->directorsOnly = ($result["dironly"]>0)?true:false;
				$this->homesystemID = $result["homesystemid"];
				$this->name = $result["name"];
				$this->systemName = $result["homesystemname"];
				$this->autoNameNewWormholes = $result["autoname_whs"];
				$this->prio = $result["prio"];
				$this->deleted = ($result["deleted"]>0)?true:false;
				$this->lastActive = $result["lastmapupdatedate"];
			}
		}

		function store()
		{
			if ($this->authgroupID == 0)
			{
				foreach (\User::getUSER()->getAuthGroupsIDs() as $groupID) {
					$this->authgroupID = $groupID;
					break;
				}
			}

			$data = array(	"authgroupid"	=> $this->authgroupID,
							"dironly"		=> ($this->directorsOnly)?1:0,
							"homesystemid"	=> $this->homesystemID,
							"name"			=> $this->name,
							"homesystemname"=> $this->systemName,
							"autoname_whs"	=> $this->autoNameNewWormholes,
							"prio"			=> $this->prio,
							"deleted"		=> ($this->deleted)?1:0);
			if ($this->id != 0)
				$data["id"] = $this->id;

			$result = \MySQL::getDB()->updateinsert("mapwormholechains", $data, array("id" => $this->id));
			if ($this->id == 0)
			{
				$this->id = $result;

				// Home systeem toevoegen
				$this->addHomeSystemToMap(false);
			}

			if ($this->corporations !== null)
			{
				$this->deleteCorporations();
				foreach ($this->getCorporations() as $corp)
				{
					$data = array(	"chainid"	=> $this->id,
									"corpid"	=> $corp->id,
									"readonly"	=> 1,
									"canadmin"	=> 1);
					\MySQL::getDB()->insert("mapwormholechains_corporations", $data);
				}
			}

			if ($this->alliances !== null)
			{
				$this->deleteAlliances();
				foreach ($this->getAlliances() as $alliance)
				{
					$data = array(	"chainid"	=> $this->id,
									"allianceid"=> $alliance->id,
									"readonly"	=> 1,
									"canadmin"	=> 1);
					\MySQL::getDB()->insert("mapwormholechains_alliances", $data);
				}
			}

			// Cache opruimen
			$this->resetCache();
			foreach ($this->getAllowedUsers() as $user) {
				$user->resetCache();
			}
		}

		/**
		 * Get allowed users
		 * @return \users\model\User[]
		 */
		function getAllowedUsers()
		{
			$allowedusers = array();

			foreach ($this->getAllowedCorporations() as $corp) {
				foreach (\users\model\User::getUsersByCorporation($corp->id) as $user) {
					$allowedusers[] = $user;
				}
			}

			return $allowedusers;
		}

		function getAllowedAdmin()
		{
			if (\User::getUSER()->getIsSysAdmin())
				return true;

			// Is deze persoon director?
			if (\User::getUSER()->getIsDirector())
			{
				// Is hij ook director van de juiste corp?
				foreach ($this->getAllowedCorporations() as $corp)
				{
					if (\User::getUSER()->getIsDirector($corp->id))
						return true;
				}
			}

			return false;
		}

		/**
		 * Get homeystem
		 * @return \scanning\model\System
		 */
		function getHomeSystem()
		{
			\AppRoot::debug("getHomesystem(".$this->homesystemID.")");
			if ($this->homesystem == null)
				$this->homesystem = new \scanning\model\System($this->homesystemID);

			return $this->homesystem;
		}

		function getAuthGroup()
		{
			if ($this->authgroup == null && $this->authgroupID > 0)
				$this->authgroup = new \admin\model\AuthGroup($this->authgroupID);

			return $this->authgroup;
		}

		/**
		 * Get allowed corporations
		 * @return \eve\model\Corporation[]
		 */
		function getAllowedCorporations()
		{
			$corporations = array();
			foreach ($this->getCorporations() as $corp) {
				$corporations[$corp->id] = $corp;
			}
			foreach ($this->getAlliances() as $ally) {
				foreach ($ally->getCorporations() as $corp) {
					$corporations[$corp->id] = $corp;
				}
			}
			return $corporations;
		}

		/**
		 * Get corporations
		 * @return \eve\model\Corporation[]
		 */
		function getCorporations()
		{
			if ($this->corporations == null)
			{
				$this->resetCorporations();
				if ($results = \MySQL::getDB()->getRows("SELECT c.*
														FROM 	corporations c
															INNER JOIN  mapwormholechains_corporations cc ON cc.corpid = c.id
														WHERE 	cc.chainid = ?
														ORDER BY c.name ASC"
											, array($this->id)))
				{
					foreach ($results as $result)
					{
						$corp = new \eve\model\Corporation();
						$corp->load($result);
						$this->corporations[] = $corp;
					}
				}
			}

			return $this->corporations;
		}

		function resetCorporations()
		{
			$this->corporations = array();
		}

		function deleteCorporations()
		{
			\MySQL::getDB()->delete("mapwormholechains_corporations", array("chainid" => $this->id));
		}

		function addCorporation($id, $readonly=false, $admin=true)
		{
			if ($this->corporations === null)
				$this->resetCorporations();

			$this->corporations[] = new \eve\model\Corporation($id);
		}

		/**
		 * Get alliances
		 * @return \eve\model\Alliance[]
		 */
		function getAlliances()
		{
			if ($this->alliances == null)
			{
				$this->resetAlliances();
				if ($results = \MySQL::getDB()->getRows("SELECT a.*
														FROM 	alliances a
															INNER JOIN mapwormholechains_alliances ca ON ca.allianceid = a.id
														WHERE 	ca.chainid = ?
														ORDER BY a.name ASC"
											, array($this->id)))
				{
					foreach ($results as $result)
					{
						$ally = new \eve\model\Alliance();
						$ally->load($result);
						$this->alliances[] = $ally;
					}
				}
			}

			return $this->alliances;
		}

		function resetAlliances()
		{
			$this->alliances = array();
		}

		function deleteAlliances()
		{
			\MySQL::getDB()->delete("mapwormholechains_alliances", array("chainid" => $this->id));
		}

		function addAlliance($id, $readonly=false, $admin=true)
		{
			if ($this->alliances === null)
				$this->resetAlliances();

			$this->alliances[] = new \eve\model\Alliance($id);
		}

		/**
		 * Calculate the position of the new wormhole
		 * @param \scanning\model\Wormhole $origin
		 * @return array(x=>0,y=>0);
		 */
		function getNewWormholePosition(\scanning\model\Wormhole $origin=null)
		{
			$position = new \scanning\controller\map\positioning\Center($this);
			$position->setOrigin($origin);
			return $position->getNextPosition();
		}

		function addWormholeSystem($fromSystemID, $toSystemID, $updateCacheTimer=true)
		{
			\AppRoot::debug("=== addWormholeSystem($fromSystemID, $toSystemID, $updateCacheTimer)", true);
			if ($fromSystemID == 0 || $toSystemID == 0)
				return false;


			// Wormholes alleen toevoegen als deze nog niet bestaan.
			$fromWormhole = \scanning\model\Wormhole::getWormholeBySystemID($fromSystemID, $this->id);
			$toWormhole = \scanning\model\Wormhole::getWormholeBySystemID($toSystemID, $this->id);

			if ($fromWormhole == null && $toWormhole == null)
			{
				// Beide systemen staan er nog niet. Voeg toe helemaal onder aan de chain op de map.
				\AppRoot::debug("both systems are not on yet!");
				$newX = 50;
				$newY = 50;

				if ($result = \MySQL::getDB()->getRow("SELECT MAX(y) AS y FROM mapwormholes WHERE chainid = ?", array($this->id)))
					$newY = $result["y"] + \scanning\Wormhole::$defaultHeight + \scanning\Wormhole::$defaultOffset + 20;

				$this->addSolarSystem($fromSystemID, $newX, $newY);
				return $this->addWormholeSystem($fromSystemID, $toSystemID);
			}

			$originHole = null;
			$addingHole = null;
			$addSystemID = null;

			if ($fromWormhole == null)
			{
				$originHole = $toWormhole;
				$addingHole = $fromWormhole;
				$addSystemID = $fromSystemID;
			}
			else if ($toWormhole == null)
			{
				$originHole = $fromWormhole;
				$addingHole = $toWormhole;
				$addSystemID = $toSystemID;
			}
			else
			{
				$originHole = $fromWormhole;
				$addingHole = $toWormhole;
			}


			// Voeg toe aan map
			if ($addSystemID !== null)
			{
				\AppRoot::debug("Voeg toe aan map");
				$position = $this->getNewWormholePosition($originHole);
				$addingHole = $this->addSolarSystem($addSystemID, $position["x"], $position["y"]);
			}


			// Verbinding toevoegen
			\AppRoot::debug("Verbinding toevoegen");
			$this->addWormholeConnectionByWormhole($originHole->id, $addingHole->id, false);



			// Nieuw systeem is toegevoegd.
			if ($addSystemID !== null)
			{
				// Wat zou de naam moeten zijn?
				if ($this->autoNameNewWormholes > 0)
				{
					$reservation = null;
					$wormholeName = $this->nameNewWormhole($addingHole, true);

					// Check of die naam gereserveerd is
					foreach ($this->getWormholes() as $whole)
					{
						if ($whole->isReservation()
							 && (strtolower($whole->name) == strtolower($wormholeName))
							 && ($whole->id != $addingHole->id))
						{
							$reservation = $whole;
							$reservation->name = $wormholeName;
							$reservation->store();
						}
					}

					if ($reservation !== null)
					{
						\AppRoot::debug("Reservation: <pre>".print_r($reservation,true)."</pre>");
						\AppRoot::debug("addingHole: <pre>".print_r($addingHole,true)."</pre>");

						$addingHole->delete();
						$reservation->solarSystemID = $addingHole->solarSystemID;
						$reservation->store();

						// Verbinding op properties zetten.
						$connection = \scanning\model\Connection::getConnectionByWormhole($reservation->id, $originHole->id, $this->id);
						if ($connection !== null)
						{
							if (($reservation->getSolarsystem() !== null && $reservation->getSolarsystem()->isCapitalCapable()) &&
								($originHole->getSolarsystem() !== null && $originHole->getSolarsystem()->isCapitalCapable()))
							{
								$connection->allowCapitals = true;
								$connection->store();
							}
							else
							{
								$connection->allowCapitals = false;
								$connection->store();
							}

							if (($reservation->getSolarsystem() !== null && $reservation->getSolarsystem()->isFrigateOnly()) &&
									($originHole->getSolarsystem() !== null && $originHole->getSolarsystem()->isFrigateOnly()))
							{
								$connection->frigateHole = true;
								$connection->store();
							}
						}
					}
					else
					{
						$addingHole->name = $this->nameNewWormhole($addingHole);
						$addingHole->store();
					}
				}


				// Check of het nieuwe systeem op een andere chain staat.
				/*
				foreach (\scanning\model\Wormhole::getWormholesByAuthgroup($this->authgroupID, $addingHole->solarSystemID) as $wormhole)
				{
					if ($wormhole->chainID == $this->id)
						continue;

					// Voeg dit systeem ook toe aan de andere chain!
					$wormhole->getChain()->addWormholeSystem($addingHole->solarSystemID, $originHole->solarSystemID);

					// We hebben iets gevonden. Kopieeren!
					$addingHole->status = $wormhole->status;
					$addingHole->fullScanDate = $wormhole->fullScanDate;
					$addingHole->fullScanDateBy = $wormhole->fullScanDateBy;
					$addingHole->store();

					// Haal connecties
					foreach ($wormhole->getConnections() as $wcon)
					{
						if ($wcon->getAgeInHours() >= 48)
							continue;

						$fromWormhole = $addingHole;
						$toWormhole = ($wormhole->id == $wcon->fromWormholeID) ? $wcon->getToWormhole() : $wcon->getFromWormhole();

						$this->addWormholeSystem($fromWormhole->solarSystemID, $toWormhole->solarSystemID);

						// Kopieer wormholesystem properties
						$newWormhole = \scanning\model\Wormhole::getWormholeBySystemID($toSolarsystemID, $this->id);
						if ($newWormhole !== null)
						{
							$newWormhole->status = $toWormhole->status;
							$newWormhole->fullScanDate = $toWormhole->fullScanDate;
							$newWormhole->fullScanDateBy = $toWormhole->fullScanDateBy;
							$newWormhole->store();
						}

						// Kopieer de connectie properties
						$newConnection = \scanning\model\Connection::getConnectionByLocations($fromWormhole->solarSystemID, $toWormhole->solarSystemID, $this->id);
						if ($newConnection != null)
						{
							foreach (get_object_vars($wcon) as $var => $val)
							{
								if (!in_array($var, array("id","chainID","fromWormholeID","toWormholeID")))
									$newConnection->$var = $val;
							}
							$newConnection->store();
							$newConnection->addBy = $wcon->addBy;
							$newConnection->addDate = $wcon->addDate;
							$newConnection->store();
						}
					}
				}
				// Check of het oude systeem op een andere chain staat. Zo ja, ook daar toevoegen!
				foreach (\scanning\model\Wormhole::getWormholesByAuthgroup($this->authgroupID, $originHole->solarSystemID) as $wormhole)
				{
					if ($wormhole->chainID == $this->id)
						continue;

					// Voeg dit systeem ook toe aan de andere chain!
					$wormhole->getChain()->addWormholeSystem($addingHole->solarSystemID, $originHole->solarSystemID);
				}
				*/
			}


			// Cache-update-timer
			if ($updateCacheTimer)
				$this->setMapUpdateDate();

			\AppRoot::debug("/== addWormholeSystem($fromSystemID, $toSystemID, $updateCacheTimer)");
			return true;
		}

        /**
         * Add actual system
         * @param integer $systemID
         * @param integer $posX
         * @param integer $posY
         * @param string  $name
         * @param string  $sigID
         * @return Wormhole
         */
		public function addSolarSystem($systemID, $posX, $posY, $name=null, $sigID=null)
		{
			$system = new \scanning\model\Wormhole();
			$system->chainID = $this->id;
			$system->signatureID = $sigID;
			$system->solarSystemID = $systemID;
			$system->x = $posX;
			$system->y = $posY;

			if ($name !== null)
				$system->name = $name;

			$system->store();

			return $system;
		}

		public function nameNewWormhole(\scanning\model\Wormhole $system, $ignoreReservations=false)
		{
			if ((int)$this->autoNameNewWormholes == 1)
				return $this->nameNewWormholesByLetters($system, $ignoreReservations);

			if ((int)$this->autoNameNewWormholes == 2)
				return $this->nameNewWormholesByNumbers($system, $ignoreReservations);

			return false;
		}

		private function nameNewWormholesByNumbers(\scanning\model\Wormhole $system, $ignoreReservations=false)
		{
			if ($system == null)
				return false;

			$letters = "abcdefghijklmnopqrstuvwxyz";

			$classname = strtolower($system->getSolarsystem()->getClass(true));
			$connectedSystems = $system->getConnectedSystems();

			// Wat is het vorige wormhole?
			if (count($connectedSystems) > 0)
			{
				// We gaan er even vanuit dat het vorige systeem eerder toegevoegd is dan al de rest
				$previousWormhole = array_shift($connectedSystems);
				$previousSystem = $previousWormhole->getSolarsystem();

				$startingName = $previousWormhole->name;
				\AppRoot::debug("Startingsystem: ".$startingName);
				if ($this->getHomeSystem()->id == $previousSystem->id)
					$startingName = "";


				if ($system->getSolarsystem()->isWSpace())
				{
					// Ben ik de static?
					$statics = array();
					foreach ($previousSystem->getStatics(false,false) as $static) {
						$statics[] = strtolower($static["tag"]);
					}


					// Tellen bij 2, tenzij we de static zouden kunnen zijn.
					$index = 2;
					if (in_array($classname, $statics))
						$index = 1;
				}
				else
				{
					$startingName .= ucfirst($classname[0]);
					$index = 0;
				}

				do
				{
					if ($system->getSolarsystem()->isWSpace())
						$title = $startingName.$index;
					else
						$title = $startingName.$letters[$index];

					$searches = array("11111111","1111111","111111","11111","1111");
					$replacements = array("8x1","7x1","6x1","5x1","4x1");
					$title = str_replace($searches, $replacements, $title);

					$searches = array("22222222","2222222","222222","22222","2222");
					$replacements = array("8x2","7x2","6x2","5x2","4x2");
					$title = str_replace($searches, $replacements, $title);

					$searches = array("33333333","3333333","333333","33333","3333");
					$replacements = array("8x3","7x3","6x3","5x3","4x3");
					$title = str_replace($searches, $replacements, $title);

					$exists = false;
					foreach ($this->getWormholes() as $hole)
					{
						if ($hole->isReservation() && $ignoreReservations)
							continue;

						if ($hole->name == $title) {
							$exists = true;
							break;
						}
					}

					if ($exists)
						$index++;
				}
				while ($exists);

				return $title;
			}

			return false;
		}

		private function nameNewWormholesByLetters(\scanning\model\Wormhole $system, $ignoreReservations=false)
		{
			$letters = "abcdefghijklmnopqrstuvwxyz";

			$startIndex = 0;
			$classname = strtolower($system->getSolarsystem()->getClass(true));

			// Ben ik verbonden met homesystem?
			if ($system->isConnectedTo($this->homesystemID))
			{
				// Ben ik de static?
				$statics = array();
				foreach ($this->getHomeSystem()->getStatics(false,false) as $static) {
					$statics[] = strtolower($static["tag"]);
				}

				if (in_array($classname, $statics))
				{
					// Ik zou de static kunnen zijn.
					$title = $classname."s";

					// Bestaat deze naam al?
					$exists = false;
					foreach ($this->getWormholes() as $hole)
					{
						if ($hole->isReservation() && $ignoreReservations)
							continue;
						if (strtolower($hole->name) == $title)
							$exists = true;
					}

					if (!$exists)
						return $title;
				}

				// Ik ben niet de static. Maar wel verbonden met home, dus xyz.
				$startIndex = 23;
			}

			$exists = false;
			$title = $classname;
			do
			{
				$exists = false;
				$title = $classname.$letters[$startIndex];
				foreach ($this->getWormholes() as $hole)
				{
					if ($hole->isReservation() && $ignoreReservations)
						continue;
					if (strtolower($hole->name) == $title) {
						$exists = true;
						break;
					}
				}

				if ($exists)
					$startIndex++;
			}
			while ($exists);

			return $title;
		}

		/**
		 * Add connection by wormhole
		 * @param integer $whFromID
		 * @param integer $whToID
		 * @param boolean $updateCacheTimer
		 * @return \scanning\model\Connection
		 */
		function addWormholeConnectionByWormhole($whFromID, $whToID, $updateCacheTimer=true)
		{
			$connection = \scanning\model\Connection::getConnectionByWormhole($whFromID, $whToID, $this->id);
			if ($connection == null)
			{
				$connection = new \scanning\model\Connection();
				$connection->fromWormholeID = $whFromID;
				$connection->toWormholeID = $whToID;
				$connection->chainID = $this->id;
				$connection->store();
			}
			if ($updateCacheTimer)
				$this->setMapUpdateDate();

			return $connection;
		}

		function addWormholeConnectionBySystem($sysFromID, $sysToID, $updateCacheTimer=true)
		{
			$connection = \scanning\model\Connection::getConnectionByLocations($sysFromID, $sysToID, $this->id);
			if ($connection == null)
			{
				$fromWormhole = \scanning\model\Wormhole::getWormholeBySystemID($sysFromID, $this->id);
				$toWormhole = \scanning\model\Wormhole::getWormholeBySystemID($sysToID, $this->id);

				$connection = new \scanning\model\Wormhole();
				$connection->fromWormholeID = $fromWormhole->id;
				$connection->toWormholeID = $toWormhole->id;
				$connection->chainID = $this->id;
				$connection->store();
			}

			if ($updateCacheTimer)
				$this->setMapUpdateDate();

			return $connection;
		}

		/** DEPRICATED **/
		function addWormholeConnection($from, $to, $updateCacheTimer=true)
		{
			$this->addWormholeConnectionBySystem($from, $to, $updateCacheTimer);
		}

		function addHomeSystemToMap($updateCacheTimer=true)
		{
			if ($this->homesystemID == 0)
				$this->load();

			if (!\scanning\Wormhole::getWormholeIdBySystem($this->homesystemID, $this->id))
			{
				$wh = new \scanning\Wormhole();
				$wh->chainID = $this->id;
				$wh->solarSystemID = $this->homesystemID;
				$wh->name = $this->systemName;
				$wh->x = 50;
				$wh->y = 50;
				$wh->store();
			}

			if ($updateCacheTimer)
				$this->setMapUpdateDate();
		}

		function moveWormhole($wormholeID, $x, $y, $updateCacheTimer=true, $modifier=25)
		{
			// Netjes alignen
			$wormhole = new \scanning\model\Wormhole($wormholeID);
			$wormhole->x = $x;
			$wormhole->y = $y;
			$wormhole->store($modifier);

			if ($updateCacheTimer)
				$this->setMapUpdateDate();
		}

		function updateWormholeSystem($systemID, $name=false, $status=false, $notes=false, $updateCacheTimer=true)
		{
			$data = array("updatedate" => date("Y-m-d H:i:s"));
			if ($name)
				$data["name"] = $name;
			if ($status !== false)
				$data["status"] = $status;
			$who = array("solarsystemid" => $systemID, "chainid" => $this->id);
			\MySQL::getDB()->update("mapwormholes", $data, $who);

			// Notes opslaan
			\MySQL::getDB()->updateinsert("mapwormholenotes",
						array(	"solarsystemid" => $systemID,
								"notes"			=> $notes,
								"updatedate"	=> date("Y-m-d H:i:s")),
						array(	"solarsystemid" => $systemID));

			if ($updateCacheTimer)
				$this->setMapUpdateDate();
		}

		/**
		 * Remove wormhole
		 * @param \scanning\model\Wormhole $wormhole
		 * @param boolean $updateCacheTimer false
		 */
		function removeWormhole(\scanning\model\Wormhole $wormhole, $updateCacheTimer=true)
		{
			if ($wormhole->getSolarsystem() !== null)
			{
				$extrainfo = array(	"delete-all"=> false,
									"wormhole"	=> array("id" 	=> $wormhole->id,
														"name"	=> $wormhole->name),
									"system"	=> array("id" 	=> $wormhole->getSolarsystem()->id,
														"name"	=> $wormhole->getSolarsystem()->name." - ".$wormhole->name),
									"chain"		=> array("id"	=> $this->id,
														"name"	=> $this->name));
				\User::getUSER()->addLog("delete-wormhole", $wormhole->solarSystemID, $extrainfo);
			}

			\MySQL::getDB()->delete("mapwormholes", array("id" => $wormhole->id));
			$this->removeWormholeConnection($wormhole->id, false);
			$this->addHomeSystemToMap(false);

			if ($updateCacheTimer)
				$this->setMapUpdateDate();
		}

		function removeWormholeSystem($wormholeID, $updateCacheTimer=true)
		{
			$wormhole = new \scanning\model\Wormhole($wormholeID);
			$this->removeWormhole($wormhole, $updateCacheTimer);
		}

		function removeConnectedWormholes($wormholeID)
		{
			$wormhole = new \scanning\model\Wormhole($wormholeID);
			$connectedWormholes = $wormhole->getConnectedSystems();
			$this->removeWormhole($wormhole, false);

			if ($wormhole->getSolarsystem() !== null) {
				foreach ($connectedWormholes as $wh) {
					$this->removeConnectedWormholes($wh->id);
				}
			}
		}

		function removeWormholeConnection($wormholeID, $updateCacheTimer=true)
		{
			\MySQL::getDB()->delete("mapwormholeconnections", array("fromwormholeid" => $wormholeID, "chainid" => $this->id));
			\MySQL::getDB()->delete("mapwormholeconnections", array("towormholeid" => $wormholeID, "chainid" => $this->id));

			if ($updateCacheTimer)
				$this->setMapUpdateDate();
		}

		function clearChain($updateCacheTimer=true)
		{
			$extrainfo = array(	"delete-all"=> true,
								"chain"		=> array("id"	=> $this->id,
													"name"	=> $this->name));
			\User::getUSER()->addLog("delete-wormhole", $this->id, $extrainfo);

			\MySQL::getDB()->delete("mapwormholes", array("chainid" => $this->id, "permanent" => 0));
			\MySQL::getDB()->delete("mapwormholeconnections", array("chainid" => $this->id));
			$this->addHomeSystemToMap(false);

			if ($updateCacheTimer)
				$this->setMapUpdateDate();
		}

		function setMapUpdateDate($datetime=false)
		{
			if (!$datetime)
				$datetime = date("Y-m-d H:i:s");

			\MySQL::getDB()->update("mapwormholechains",
									array("lastmapupdatedate" => date("Y-m-d H:i:s", strtotime($datetime))),
									array("id" => $this->id));
		}

		function setSignaturesUpdateDate($datetime=false)
		{
			if (!$datetime)
				$datetime = date("Y-m-d H:i:s");

			\MySQL::getDB()->update("mapwormholechains",
									array("lastsignatureupdatedate" => date("Y-m-d H:i:s", strtotime($datetime))),
									array("id" => $this->id));
		}


		/**
		 * Get wormholes in this chain
		 * @return \scanning\model\Wormhole[]
		 */
		function getWormholes()
		{
			if ($this->wormholes === null)
				$this->wormholes = \scanning\model\Wormhole::getWormholesByChain($this->id);

			return $this->wormholes;
		}



		/**
		 * Get current selected chain
		 * @return \scanning\model\Chain
		 */
		public static function getCurrentChain()
		{
			if (self::$currentChain === null)
				self::$currentChain = new \scanning\model\Chain(\User::getSelectedChain());

			return self::$currentChain;
		}

		/**
		 * Get chains by authorization group
		 * @param integer $authgroupID
		 * @return \scanning\model\Chain[]
		 */
		public static function getChainsByAuthgroup($authgroupID)
		{
			$chains = array();
			if ($results = \MySQL::getDB()->getRows("SELECT *
													FROM 	mapwormholechains
													WHERE 	authgroupid = ?
													ORDER BY prio, id"
										, array($authgroupID)))
			{
				foreach ($results as $result)
				{
					$chain = new \scanning\model\Chain();
					$chain->load($result);
					$chains[] = $chain;
				}
			}
			return $chains;
		}

		/**
		 * Get chains by home system id
		 * @param integer $solarSystemID
		 * @return \scanning\model\Chain[]
		 */
		public static function getChainsByHomesystem($solarSystemID)
		{
			$chains = array();
			if ($results = \MySQL::getDB()->getRows("SELECT *
													FROM 	mapwormholechains
													WHERE 	homesystemid = ?
													ORDER BY prio, id"
										, array($solarSystemID)))
			{
				foreach ($results as $result)
				{
					$chain = new \scanning\model\Chain();
					$chain->load($result);
					$chains[] = $chain;
				}
			}
			return $chains;
		}
	}
}
?>