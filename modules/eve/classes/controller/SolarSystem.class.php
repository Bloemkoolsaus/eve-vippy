<?php
namespace eve\controller
{
    class SolarSystem
    {
        /**
         * Get solarsystem by field
         * @param string $fromfield
         * @param string $value
         * @return \eve\model\Solarsystem|false is solarsystem does not exist
         */
        private function getSolarsystemByValue($fromfield, $value)
        {
            if ($result = \MySQL::getDB()->getRow("SELECT * FROM ".\eve\Module::eveDB().".mapsolarsystems WHERE ".$fromfield." = ?", array($value)))
            {
                $solarsystem = new \eve\model\SolarSystem();
                $solarsystem->load($result);
                return $solarsystem;
            }

            return false;
        }

        /**
         * Get solarsystem by id
         * @param string $id
         * @return \eve\model\Solarsystem|false is solarsystem does not exist
         */
        public function getSolarsystemByID($id)
        {
            return new \eve\model\SolarSystem($id);
        }

        /**
         * Get solarsystem by name
         * @param string $name
         * @return \eve\model\Solarsystem|false is solarsystem does not exist
         */
        public function getSolarsystemByName($name)
        {
            $system = \eve\model\SolarSystem::getSolarsystemByName($name);
            if ($system != null)
                return $system;

            return false;
        }

        /**
         * Get wormhole class. If kspace, return 0
         * @param integer $region
         * @return number
         */
        public function getClassByRegion($region)
        {
            $region = $region-0;
            if ($region >= 11000030)
                return 6;
            else if ($region > 11000023)
                return 5;
            else if ($region > 11000015)
                return 4;
            else if ($region > 11000008)
                return 3;
            else if ($region > 11000003)
                return 2;
            else if ($region > 10999999)
                return 1;
            else
                return 0;
        }

        public function parseSecStatus($security, $short=true)
        {
            $security = round($security*10000)/10000;
            if ($security >= 0.45)
                return ($short)?"HS":"High-Sec";
            else if ($security >= 0)
                return ($short)?"LS":"Low-Sec";
            else
                return ($short)?"NS":"Null-Sec";
        }

        public function getWormholeEffect($systemID)
        {
            \AppRoot::debug("getWormholeEffect()");

            if ($result = \MySQL::getDB()->getRow("	SELECT 	t.typename, c.wormholeclassid
													FROM 	".\eve\Module::eveDB().".mapdenormalize n
														LEFT JOIN ".\eve\Module::eveDB().".invtypes t ON n.typeid = t.typeid
														LEFT JOIN maplocationwormholeclasses c ON n.regionid = c.locationid
													WHERE 	n.solarsystemid = ?
													AND 	n.groupid = '995'"
                , array($systemID)))
            {
                return $result["typename"];
            }
            else
                return false;
        }

        public function getWormholeStatus($systemID)
        {
            \AppRoot::debug("getWormholeStatus($systemID)");

            $status = 1;
            if ($result = \MySQL::getDB()->getRow("SELECT status FROM mapwormholes WHERE solarsystemid = ? AND chainid = ?"
                , array($systemID, \User::getSelectedChain())))
            {
                $status = $result["status"];
            }

            return $status;
        }

        public function getWormholeStatics($systemID)
        {
            \AppRoot::debug("getWormholeStatics()");

            $statics = array();
            if ($results = \MySQL::getDB()->getRows("SELECT	t.id, t.name as signame, c.name, c.tag, c.color
													FROM	".\eve\Module::eveDB().".mapsolarsystems m
														INNER JOIN mapwormholestatics s ON s.solarsystemid = m.solarsystemid
														INNER JOIN mapwormholetypes t ON t.id = s.whtypeid
														INNER JOIN mapsolarsystemclasses c ON c.id = t.destination
													WHERE   m.solarsystemid = ?
													ORDER BY tag DESC"
                , array($systemID)))
            {
                $kspaceTags = ["HS","LS","NS"];
                foreach ($results as $result)
                {
                    $statics[$result["signame"]]["id"] = $result["id"];
                    $statics[$result["signame"]]["tag"] = $result["tag"];
                    $statics[$result["signame"]]["name"] = $result["name"];
                    $statics[$result["signame"]]["wspace"] = (in_array($result["tag"], $kspaceTags))?false:true;
                }
            }

            return $statics;
        }

        public function getWormholeAnomalies($systemID)
        {
            return \scanning\Anomaly::getSystemAnomalies($systemID);
        }

        public function getStations($systemID)
        {
            \AppRoot::debug("getStations()");

            $stations = array();
            if ($results = \MySQL::getDB()->getRows("SELECT	stationid, stationname
													FROM	".\eve\Module::eveDB().".stastations
													WHERE	solarsystemid = ?"
                , array($systemID)))
            {
                foreach ($results as $result) {
                    $stations[] = $result;
                }
            }
            return $stations;
        }

        public function getTradeHubs()
        {
            \AppRoot::debug("getTradeHubs()");

            $hubs = array();
            if ($cache = \Cache::file()->get("tradehubs/hubs.json"))
                $hubs = json_decode($cache,true);
            else
            {
                if ($results = \MySQL::getDB()->getRows("SELECT * FROM maptradehubs"))
                {
                    foreach ($results as $result) {
                        $hubs[] = $result["solarsystemid"];
                    }
                }
                \Cache::file()->set("tradehubs/hubs.json", json_encode($hubs));
            }

            return $hubs;
        }

        public function getClosestTradehub($systemID)
        {
            \AppRoot::debug("getClosestTradehub(".$systemID.")");
            $closest = array();

            $system = new \eve\model\SolarSystem($systemID);
            if ($system->isWSpace()) {
                \AppRoot::debug("w-space... abort finding tadehubs");
                return null;
            }

            // Kijk eerst in cache.
            if ($result = \MySQL::getDB()->getRow("SELECT * FROM mapclosesttradehub WHERE systemid = ?", array($system->id)))
            {
                $tradehub = $this->getSolarsystemByID($result["hubid"]);

                $closest["systemid"] = $tradehub->id;
                $closest["systemname"] = $tradehub->name;
                $closest["nrjumps"] = $result["nrjumps"];

                // Get tradehub station
                $closest["stationid"] = 0;
                if ($station = \MySQL::getDB()->getRow("SELECT * FROM maptradehubs WHERE solarsystemid = ?", array($tradehub->id)))
                    $closest["stationid"] = $station["stationid"];

                if ($closest["nrjumps"] > 0)
                    return $closest;
            }

            \AppRoot::debug("-- calculate --");
            // Uitrekenen
            $hubs = $this->getTradeHubs();
            $leastNrJumps = 0;
            foreach ($hubs as $hubID)
            {
                $nrJumps = $this->getNrJumps($system->id, $hubID);
                if ($leastNrJumps == 0 || $nrJumps < $leastNrJumps)
                {
                    $tradehub = $this->getSolarsystemByID($hubID);

                    $closest["systemid"] = $tradehub->id;
                    $closest["systemname"] = $tradehub->name;
                    $closest["nrjumps"] = $nrJumps;
                    $leastNrJumps = $nrJumps;
                }
            }

            \MySQL::getDB()->insert("mapclosesttradehub",
                array(	"systemid" 	=> $system->id,
                          "hubid" 	=> $closest["systemid"],
                          "nrjumps" 	=> $closest["nrjumps"]));

            return $closest;
        }

        /**
         * Aantal cyno jumps tussen 2 systemen.
         * @param int $startSystemID
         * @param int $destinationSystemID
         * @param int $shipTypeID
         * @param boolean $stationOnly		alleen met dockable midways?
         */
        public function getNrCynoJumps($startSystemID, $destinationSystemID, $maxJumpRange)
        {
            \AppRoot::setMaxExecTime(0);
            \AppRoot::debug("getNrCynoJumps(".$startSystemID.",".$destinationSystemID.",".$maxJumpRange.")");

            if ($result = \MySQL::getDB()->getRow("	SELECT 	*
													FROM 	mapsolarsystemcynojumps
													WHERE	(startid = ? AND destid = ?
													 	OR	startid = ? AND destid = ?)
													AND		maxjumprange = ?"
                , array($startSystemID, $destinationSystemID,
                    $destinationSystemID, $startSystemID,
                    $maxJumpRange)))
            {
                return $result["nrjumps"];
            }
            else
            {
                // Start rekenen!
                $open = array();
                $closed = array();

                $sid = $startSystemID;
                $did = $destinationSystemID;

                $open[$sid]['weight'] = 0;
                $open[$sid]['parent'] = NULL;
                $open[$sid]['sid'] = $sid;

                do
                {
                    foreach ($open as $value)
                    {
                        $sid = $value['sid'];
                        $weight = $value['weight'];
                        $parent = $value['parent'];

                        $closed[$sid]['weight'] = $weight;
                        $closed[$sid]['parent'] = $parent;
                        $closed[$sid]['sid'] = $sid;

                        if ($sid == $did)
                        {
                            // We hebben em!!! Opslaan in cache
                            \MySQL::getDB()->insert("mapsolarsystemcynojumps",
                                array(	"startid" => $startSystemID,
                                          "destid" => $destinationSystemID,
                                          "maxjumprange" => $maxJumpRange,
                                          "nrjumps" => $weight));
                            return $weight;
                        }
                        else
                        {
                            $system = new \eve\model\SolarSystem($sid);
                            foreach ($system->getSystemsInJumpRange($maxJumpRange) as $sys)
                            {
                                $nsid = $sys->id;
                                $nweight = $weight + 1;
                                $nparent = $system->id;

                                if (!isset($closed[$nsid]['weight']) || ($closed[$nsid]['weight'] >= $nweight))
                                {
                                    $open[$nsid]['weight'] = $nweight;
                                    $open[$nsid]['parent'] = $sid;
                                    $open[$nsid]['sid'] = $nsid;
                                }
                            }
                            unset($open[$sid]);
                        }
                    }
                }
                while (count($open) > 0);
            }
            return null;
        }

        /**
         * Aantal normale gate-jumps tussen 2 systemen.
         * @param int $startSystemID
         * @param int $destinationSystemID
         * @param int $minSecurity minimale sec-status van systemen.
         * @return integer|null
         */
        public function getNrJumps($startSystemID, $destinationSystemID, $minSecurity=null)
        {
            \AppRoot::debug("getNrJumps(".$startSystemID.",".$destinationSystemID.")");

            if ($startSystemID == $destinationSystemID)
                return 0;

            // Eerst cache bekijken
            $query = array();
            if ($minSecurity == null)
                $query[] = " minsecurity is null";
            else
                $query[] = " minsecurity = ".round($minSecurity*100);

            if ($result = \MySQL::getDB()->getRow("	SELECT * FROM mapnrofjumps
													WHERE	((startid = ? AND destid = ?)
														OR	(destid = ? AND startid = ?))
													AND 	".implode(" AND ",$query),
                                        array(	$startSystemID, $destinationSystemID,
                                            $startSystemID, $destinationSystemID)))
            {
                // Ophalen uit database cache.
                return $result["nrjumps"];
            }
            else
            {
                // Start rekenen!
                $open = array();
                $closed = array();

                $sid = $startSystemID;
                $did = $destinationSystemID;

                $open[$sid]['weight'] = 0;
                $open[$sid]['parent'] = NULL;
                $open[$sid]['sid'] = $sid;

                do
                {
                    foreach ($open as $value)
                    {
                        $sid = $value['sid'];
                        $weight = $value['weight'];
                        $parent = $value['parent'];

                        $closed[$sid]['weight'] = $weight;
                        $closed[$sid]['parent'] = $parent;
                        $closed[$sid]['sid'] = $sid;

                        if ($sid == $did)
                        {
                            // We hebben em!!! Opslaan in cache
                            \MySQL::getDB()->insert("mapnrofjumps",
                                array(	"startid" => $startSystemID,
                                          "destid" => $destinationSystemID,
                                          "nrjumps" => $weight,
                                          "minsecurity" => round($minSecurity*100)));
                            return $weight;
                        }
                        else
                        {
                            if ($results = \MySQL::getDB()->getRows("SELECT tosolarsystemid
																	FROM mapsolarsystemjumps
																	WHERE fromsolarsystemid = ?"
                                                            , array($sid)))
                            {
                                foreach ($results as $data)
                                {
                                    if ($minSecurity != null) {
                                        $system = new \eve\model\SolarSystem($data["tosolarsystemid"]);
                                        if ($system->security < $minSecurity)
                                            continue;
                                    }

                                    $nsid = $data["tosolarsystemid"];
                                    $nweight = $weight + 1;
                                    $nparent = $sid;

                                    if (!isset($closed[$nsid]['weight']) || ($closed[$nsid]['weight'] >= $nweight))
                                    {
                                        $open[$nsid]['weight'] = $nweight;
                                        $open[$nsid]['parent'] = $sid;
                                        $open[$nsid]['sid'] = $nsid;
                                    }
                                }
                            }
                            unset($open[$sid]);
                        }
                    }
                }
                while (count($open) > 0);
            }

            return null;
        }

        public function getNotesBySystemID($systemID)
        {
            if ($result = \MySQL::getDB()->getRow("SELECT * FROM mapwormholenotes WHERE solarsystemid = ?", array($systemID)))
                return array("notes" => nl2br($result["notes"]), "updatedate" => $result["updatedate"]);
            else
                return "";
        }
    }
}
?>