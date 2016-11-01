<?php
namespace map\controller;

class LocationTracker
{
    /**
     * Set character location
     * @param $authGroupID
     * @param $characterID
     * @param $locationID
     * @param $shipTypeID
     * @return bool
     */
    function setCharacterLocation($authGroupID, $characterID, $locationID, $shipTypeID=null)
    {
        \AppRoot::doCliOutput("setCharacterLocation($authGroupID, $characterID, $locationID, $shipTypeID)");
        if (!is_numeric($locationID))
            return false;

        $previousLocationID = null;
        /*
        $cacheFileName = "map/character/".$characterID."/location";
        $cache = \Cache::file()->get($cacheFileName);
        if ($cache) {
            // Cache. Maar is die nog recent?
            if (isset($cache["timestamp"])) {
                if (strtotime($cache["timestamp"]) > strtotime("now")-60)
                    $previousLocationID = $cache["location"];
            }
        }
        */
        if (!$previousLocationID) {
            if ($previousLocation = \MySQL::getDB()->getRow("select * from map_character_locations
                                                             where characterid = ? and lastdate > ?"
                            , [$characterID, date("Y-m-d H:i:s", strtotime("now")-60)]))
            {
                $previousLocationID = $previousLocation["solarsystemid"];
            }
        }

        // Huidige locatie opslaan.
        //\Cache::file()->set($cacheFileName, ["location" => $locationID, "timestamp" => date("Y-m-d H:i:s")]);
        \MySQL::getDB()->updateinsert("map_character_locations", [
            "characterid" => $characterID,
            "solarsystemid" => $locationID,
            "shiptypeid" => $shipTypeID,
            "authgroupid" => $authGroupID,
            "lastdate" => date("Y-m-d H:i:s")
        ],[
            "characterid" => $characterID
        ]);


        $chainMaps = \map\model\Map::findAll(["authgroupid" => $authGroupID]);
        foreach ($chainMaps as $map) {
            $map->setMapUpdateDate();
        }

        if ($previousLocationID)
        {
            if (!is_numeric($previousLocationID))
                return false;

            // We jumpen naar een ander systeem!
            if ($previousLocationID != $locationID)
            {
                // Pods tellen niet mee.
                if (!in_array($shipTypeID, [0, 670, 33328]))
                {
                    // Check alle maps van deze authgroup
                    foreach ($chainMaps as $map)
                    {
                        $addNewWormhole = true;
                        $wormholeFrom = null;
                        $wormholeTo = null;

                        // Staan beide systemen al op de map?
                        if ($results = \MySQL::getDB()->getRows("select *
                                                            from    mapwormholes
                                                            where   chainid = ?
                                                            and     solarsystemid in (".$previousLocationID.",".$locationID.")"
                                                , [$map->id]))
                        {
                            foreach ($results as $result)
                            {
                                $wh = new \map\model\Wormhole();
                                $wh->load($result);

                                if ($wh->solarSystemID == $previousLocationID)
                                    $wormholeFrom = $wh;
                                else
                                    $wormholeTo = $wh;
                            }
                        }

                        // Beide systemen zijn al bekend.
                        if ($wormholeTo != null && $wormholeFrom != null)
                            $addNewWormhole = false;
                        // Beide systemen zijn niet bekend.
                        if ($wormholeTo == null && $wormholeFrom == null)
                            $addNewWormhole = false;

                        $fromSystem = new \map\model\SolarSystem($previousLocationID);
                        $toSystem = new \map\model\SolarSystem($locationID);

                        // kspace kspace systems
                        if ($addNewWormhole) {
                            if (!$fromSystem->isWSpace() && !$toSystem->isWSpace()) {
                                if ($fromSystem->getNrJumpsTo($toSystem->id) <= 3) // iets hoger dan 1 door lag in de CREST tracker
                                    $addNewWormhole = false;
                            }
                        }

                        // Magic!
                        if ($addNewWormhole) {
                            $controller = new \map\controller\Wormhole();
                            $controller->addWormhole($map, $previousLocationID, $locationID);

                            // Toevoegen aan stats.
                            $char = \eve\model\Character::findByID($characterID);
                            if ($char->getUser() || \User::getUSER())
                            {
                                $stat = new \stats\model\Whmap();
                                $stat->userID = ($char->getUser())?$char->getUser()->id:\User::getUSER()->id;
                                $stat->chainID = $map->id;
                                $stat->corpID = $char->corporationID;
                                $stat->pilotID = $char->id;
                                $stat->mapdate = date("Y-m-d H:i:s");
                                $stat->systemID = $locationID;
                                $stat->store();
                            }
                        }

                        $connection = \map\model\Connection::getConnectionByLocations($previousLocationID, $locationID, $map->id);
                        if ($connection)
                        {
                            // Systems zijn connected. Lekker makkelijk, registreer jump
                            $connection->addJump($shipTypeID, $characterID, false);
                        }
                        else
                        {
                            // Systems niet connected. Zoek alle tussenliggende holes, en registreer daar ook de jump!
                            $route = new \map\model\Route();
                            $route->setMap($map);
                            $route->setFromSystem($fromSystem);
                            $route->setToSystem($toSystem);

                            $connections = $route->getConnections();
                            if ($connections) {
                                foreach ($connections as $connection) {
                                    $connection->addJump($shipTypeID, $characterID, false);
                                }
                            }
                        }
                    }
                }
            }
        }

        return false;
    }
}