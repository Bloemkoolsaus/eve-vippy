<?php
namespace scanning\model
{
	class Chain
	{
		public $id = 0;
		public $authgroupID = 0;
		public $homesystemID = 0;
		public $name;
		public $systemName;
		public $prio = 0;
		public $deleted = false;
		public $lastActive;

		private $alliances = null;
		private $corporations = null;
		private $homesystem = null;
		private $authgroup = null;
		private $wormholes = null;
		private $allowedUsers = null;
        private $settings = null;
        private $namingScheme = null;

		private static $currentChain = null;

		function __construct($id=false)
		{
			if ($id) {
				$this->id = $id;
				$this->load();
			}
		}

		private function getCacheDirectory($full=false)
		{
            $directory = "mapchain/".$this->id."/";
            if ($full)
                $directory = \Cache::file()->getDirectory().$directory;

            return $directory;
		}

		function resetCache()
		{
			\AppRoot::debug("Chain ".$this->name." (".$this->getHomeSystem()->name.")->resetCache()");
			\Tools::deleteDir($this->getCacheDirectory(true));
		}

		function load($result=false)
		{
			if (!$result)
			{
				// Check cache
				if (!\AppRoot::config("no-cache-chains"))
				{
					if ($result = \Cache::file()->get($this->getCacheDirectory()."chaininfo.json"))
						$result = json_decode($result, true);
				}

				if (!$result)
				{
					$result = \MySQL::getDB()->getRow("SELECT * FROM mapwormholechains WHERE id = ?", array($this->id));
                    \Cache::file()->set($this->getCacheDirectory()."chaininfo.json", json_encode($result));
				}
			}

			if ($result)
			{
				$this->id = $result["id"];
				$this->authgroupID = $result["authgroupid"];
				$this->homesystemID = $result["homesystemid"];
				$this->name = $result["name"];
				$this->systemName = $result["homesystemname"];
				$this->prio = $result["prio"];
                $this->deleted = ($result["deleted"]>0)?true:false;
				$this->lastActive = $result["lastmapupdatedate"];
			}
		}

        function __get($param)
        {
            //\AppRoot::depricated("__get($param)");

            if ($param == "directorsOnly")
                return $this->getSetting("directors-only");

            if ($param == "countInStats")
                return $this->getSetting("count-statistics");

            if ($param == "autoNameNewWormholes")
                return $this->getSetting("wh-autoname-scheme");

            return null;
        }

        function __set($param, $value)
        {
            \AppRoot::depricated("__set($param)");

            if ($param == "directorsOnly")
                $this->setSetting("directors-only", ($value)?1:null);

            if ($param == "countInStats")
                $this->setSetting("count-statistics", ($value)?1:null);

            if ($param == "autoNameNewWormholes")
                $this->setSetting("wh-autoname-scheme", $value);
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
							"homesystemid"	=> $this->homesystemID,
							"name"			=> $this->name,
							"homesystemname"=> $this->systemName,
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

            if ($this->settings !== null)
            {
                \MySQL::getDB()->delete("map_chain_settings", ["chainid" => $this->id]);
                foreach ($this->getSettings() as $var => $val) {
                    \MySQL::getDB()->insert("map_chain_settings", ["chainid" => $this->id, "var" => $var, "val" => $val]);
                }
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
         * Get naming scheme
         * @return \map\model\NamingScheme|null
         */
        function getNamingScheme()
        {
            if ($this->namingScheme === null)
            {
                $schemeID = $this->getSetting("wh-autoname-scheme");
                if ($schemeID != null)
                    $this->namingScheme = \map\model\NamingScheme::findByID($schemeID);
            }

            return $this->namingScheme;
        }

        /**
         * Get settings
         * @return array
         */
        function getSettings()
        {
            if ($this->settings === null)
            {
                $this->clearSettings();
                if ($results = \MySQL::getDB()->getRows("SELECT * FROM map_chain_settings WHERE chainid = ?", [$this->id]))
                {
                    foreach ($results as $result) {
                        $this->settings[$result["var"]] = $result["val"];
                    }
                }
            }

            return $this->settings;
        }

        function clearSettings()
        {
            $this->settings = [];
        }

        /**
         * Get setting
         * @param $setting
         * @return mixed|null
         */
        function getSetting($setting)
        {
            foreach ($this->getsettings() as $var => $val) {
                if ($var == $setting) {
                    \AppRoot::debug("getSetting($setting): ".$val);
                    return $val;
                }
            }

            return null;
        }

        /**
         * Set setting
         * @param $setting
         * @param $value
         */
        function setSetting($setting, $value)
        {
            $this->getSettings();
            $this->settings[$setting] = $value;

            if ($value == null)
                unset($this->settings[$setting]);
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
			if (\User::getUSER()->isAdmin())
			{
				// Is hij ook director van de juiste corp?
				foreach ($this->getAllowedCorporations() as $corp)
				{
                    foreach (\User::getUSER()->getCorporations() as $ucorp)
                    {
                        if ($ucorp == $corp->id)
                            return true;
                    }
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
            $wormhole = new \scanning\model\Wormhole($wormholeID);
            $wormhole->move($x, $y, $modifier);

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
            if (\AppRoot::isCommandline() || \User::getUSER()->isAllowedChainAction($this, "delete"))
            {
                if ($wormhole->getSolarsystem() !== null)
                {
                    $extrainfo = array("delete-all" => false,
                                       "wormhole"   => array("id"   => $wormhole->id,
                                                             "name" => $wormhole->name),
                                       "system"     => array("id"   => $wormhole->getSolarsystem()->id,
                                                             "name" => $wormhole->getSolarsystem()->name . " - " . $wormhole->name),
                                       "chain"      => array("id"   => $this->id,
                                                             "name" => $this->name));
                    if (\User::getUSER())
                        \User::getUSER()->addLog("delete-wormhole", $wormhole->solarSystemID, $extrainfo);
                }

                \MySQL::getDB()->delete("mapwormholes", array("id" => $wormhole->id));
                $this->removeWormholeConnection($wormhole->id, false);
                $this->addHomeSystemToMap(false);
            }

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

            if (!$wormhole->permanent)
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

            foreach (\scanning\model\Wormhole::getWormholesByChain($this->id) as $wormhole)
            {
                if (!$wormhole->isPermenant())
                    $wormhole->delete();
            }

			if ($updateCacheTimer)
				$this->setMapUpdateDate();
		}

		function setMapUpdateDate($datetime=false)
		{
            /**
             * Check minimale positie.
             */
            if ($result = \MySQL::getDB()->getRow("select min(x) as x, min(y) as y from mapwormholes where chainid = ?", [$this->id]))
            {
                $minX = \Config::getCONFIG()->get("map_wormhole_offset_y");
                $minY = \Config::getCONFIG()->get("map_wormhole_offset_y");

                /** Check voor negatieve posities (die vallen van de map) **/
                if ($result["x"] < $minX) {
                    $x = $result["x"]*-1+((int)\Config::getCONFIG()->get("map_wormhole_offset_x"));
                    \MySQL::getDB()->doQuery("update mapwormholes set x = x + $x where chainid = ?", [$this->id]);
                }
                if ($result["y"] < $minY) {
                    $y = $result["y"]*-1+((int)\Config::getCONFIG()->get("map_wormhole_offset_y"));
                    \MySQL::getDB()->doQuery("update mapwormholes set y = y + $y where chainid = ?", [$this->id]);
                }

                /** Te ver van de kant? **/
                if ($result["x"] > $minX) {
                    $x = $result["x"]-$minX;
                    \MySQL::getDB()->doQuery("update mapwormholes set x = x - $x where chainid = ?", [$this->id]);
                }
                if ($result["y"] > $minY) {
                    $y = $result["y"]-$minY;
                    \MySQL::getDB()->doQuery("update mapwormholes set y = y - $y where chainid = ?", [$this->id]);
                }
            }

            if (!$datetime)
                $datetime = date("Y-m-d H:i:s");

            \MySQL::getDB()->update("mapwormholechains",
                array("lastmapupdatedate" => date("Y-m-d H:i:s", strtotime($datetime)+10)),
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
         * Get wormhole by system
         * @param $systemID
         * @return \scanning\model\Wormhole|null
         */
        function getWormholeBySystem($systemID)
        {
            foreach ($this->getWormholes() as $wormhole) {
                if ($wormhole->solarSystemID == $systemID)
                    return $wormhole;
            }
            return null;
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