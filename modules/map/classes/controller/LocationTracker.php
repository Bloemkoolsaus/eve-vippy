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

        $cacheFileName = "map/character/".$characterID."/location";
        $previousLocationID = null;
        $cache = \Cache::file()->get($cacheFileName);
        if ($cache) {
            // Cache. Maar is die nog recent?
            if (isset($cache["timestamp"])) {
                if (strtotime($cache["timestamp"]) > strtotime("now")-60)
                    $previousLocationID = $cache["location"];
            }
        }
        if (!$previousLocationID) {
            if ($previousLocation = \MySQL::getDB()->getRow("select *
                                                             from   map_character_locations
                                                             where  characterid = ?
                                                             and    lastdate > ?"
                                    , [$characterID, date("Y-m-d H:i:s", strtotime("now")-60)]))
            {
                $previousLocationID = $previousLocation["solarsystemid"];
            }
        }

        // Huidige locatie opslaan.
        \Cache::file()->set($cacheFileName, ["location" => $locationID, "timestamp" => date("Y-m-d H:i:s")]);
        \MySQL::getDB()->updateinsert("map_character_locations", [
            "characterid" => $characterID,
            "solarsystemid" => $locationID,
            "shiptypeid" => $shipTypeID,
            "authgroupid" => $authGroupID,
            "lastdate" => date("Y-m-d H:i:s")
        ],[
            "characterid" => $characterID
        ]);

        $chainMaps = \map\model\Map::getChainsByAuthgroup($authGroupID);
        foreach ($chainMaps as $map) {
            $map->setMapUpdateDate();
        }

        if ($previousLocationID)
        {
            if (!is_numeric($previousLocationID))
                return true;
            if (!is_numeric($locationID))
                return true;

            // We jumpen naar een ander systeem!
            if ($previousLocationID != $locationID)
            {
                // Pods tellen niet mee.
                if (in_array($shipTypeID, [0, 670, 33328]))
                    return true;

                // Check alle maps van deze authgroup
                foreach ($chainMaps as $map)
                {
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

                    // Beide systemen zijn al bekend. We hoeven niets te doen.
                    if ($wormholeTo != null && $wormholeFrom != null)
                        continue;
                    // Beide systemen zijn niet bekend. We hoeven niets te doen.
                    if ($wormholeTo == null && $wormholeFrom == null)
                        continue;

                    // Beide systemen zijn kspace. Annuleer alle iteraties.
                    if ($wormholeTo && $wormholeTo->getSolarsystem()->isKSpace()) {
                        if ($wormholeFrom && $wormholeFrom->getSolarsystem()->isKSpace())
                            return true;
                    }

                    // Magic!
                    return $map->addWormholeSystem($previousLocationID, $locationID);
                }
            }
        }

        return false;
    }
}