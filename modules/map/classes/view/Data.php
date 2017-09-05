<?php
namespace map\view;

class Data
{
    function getOverview($arguments = array())
    {
        return $this->getMap($arguments);
    }

    function getMap($arguments=array(), \map\model\Map $map=null)
    {
        if ($map == null) {
            if (count($arguments) > 0)
                $map = \map\model\Map::findByName(array_shift($arguments));
        }

        if ($map == null)
            return "no map selected";

        // Character Locations
        $characters = array();
        if (count(\User::getUSER()->getAuthGroupsIDs()) > 0)
        {
            if ($results = \MySQL::getDB()->getRows("select	c.id, c.name, c.userid, l.solarsystemid
                                                    from	map_character_locations l
                                                        inner join characters c on c.id = l.characterid
                                                    where   l.authgroupid = ?
                                                    and		l.lastdate >= date_add(now(), interval -5 minute)
                                                    order by c.name"
                                        , [$map->getAuthGroup()->id]))
            {
                foreach ($results as $result)
                {
                    $characters[$result["solarsystemid"]][] = [
                        "id" 	=> $result["id"],
                        "name" 	=> $result["name"],
                        "isme"	=> (\User::getUSER()->id == $result["userid"])?1:0
                    ];
                }
            }
        }


        $wormholes = array();
        $connections = array();

        if ($results = \MySQL::getDB()->getRows("SELECT * FROM mapwormholes WHERE chainid = ?", array($map->id)))
        {
            foreach ($results as $result)
            {
                $wormhole = new \map\model\Wormhole();
                $wormhole->load($result);

                $data = ["id"       => $wormhole->id,
                         "name"     => $wormhole->name,
                         "titles"   => [],
                         "position" => ["x" => $wormhole->x, "y" => $wormhole->y],
                         "status"   => $wormhole->status];

                if ($wormhole->fullScanDate && strtotime($wormhole->fullScanDate) > 0)
                {
                    $age = strtotime("now")-strtotime($wormhole->fullScanDate);
                    $data["lastscan"] = floor($age/3600);
                }

                $system = $wormhole->getSolarsystem();
                if ($system != null)
                {
                    if ($map->getHomeSystem()->id == $system->id)
                        $data["status"] = 10;

                    $data["system"] = ["id"   => $system->id,
                                       "name" => $system->name,
                                       "class" => ["tag" => $system->getClass(true),
                                                   "type" => ($system->isWSpace())?"wspace":"kspace",
                                                   "color" => $system->getClassColor()]];

                    if (isset($characters[$system->id]))
                        $data["system"]["characters"] = $characters[$system->id];

                    if ($system->isWSpace())
                    {
                        foreach ($map->getAuthGroup()->getChains() as $chain)
                        {
                            if ($chain->id != $map->id)
                            {
                                if ($chain->homesystemID == $system->id)
                                {
                                    $data["titles"][] = ["title" => $chain->systemName,
                                                         "color" => "blue",
                                                         "bold"  => true];
                                }
                            }
                        }
                    }

                    if ($system->isKnownSystem())
                    {
                        $color = null;
                        if ($system->getKnownSystem()->status < 0)
                            $color = "red";
                        if ($system->getKnownSystem()->status > 0)
                            $color = "blue";
                        $data["titles"][] = ["title" => $system->getKnownSystem()->name,
                                             "color" => $color,
                                             "bold"  => true];
                    }

                    if ($system->isWSpace())
                    {
                        $data["system"]["statics"] = $system->getStatics(true);

                        if ($system->isShattered() !== false)
                        {
                            if ($system->isShattered() == "frigate")
                            {
                                $data["titles"][] = ["title" => "Small Ship Shattered",
                                                     "color" => "purple",
                                                     "bold"  => true];
                            }
                            else
                            {
                                $data["titles"][] = ["title" => "Shattered",
                                                     "color" => "purple",
                                                     "bold"  => true];
                            }
                        }
                        if ($system->getEffect())
                            $data["titles"][] = ["title" => $system->getEffect()];
                    }
                    else
                    {
                        $data["titles"][] = ["title" => $system->getRegion()->name, "bold" => true];

                        $tradeHubRoute = $system->getTradehubs();
                        if (isset($tradeHubRoute["systemname"]))
                            $data["titles"][] = ["title" => $tradeHubRoute["nrjumps"]." jumps to ".$tradeHubRoute["systemname"]];

                        if ($system->isHSIsland())
                            $data["system"]["hsisland"] = true;
                        if ($system->isDirectHS())
                            $data["system"]["direcths"] = true;
                        if ($system->hasCapsInRange())
                            $data["system"]["cyno"] = true;
                        if ($system->isFactionWarfareSystem())
                            $data["system"]["fwsystem"] = true;
                        if ($system->isContested())
                            $data["system"]["contested"] = true;
                        if ($system->getFactionID())
                            $data["system"]["faction"] = $system->getFactionID();
                    }
                }
                else
                {
                    $data["titles"][] = ["title" => "Unmapped", "bold" => true];
                }

                /**
                 * - Characters in system
                 */

                $wormholes[$wormhole->id] = $data;
            }
        }

        if ($results = \MySQL::getDB()->getRows("select * from mapwormholeconnections where chainid = ?", array($map->id)))
        {
            foreach ($results as $result)
            {
                $connection = new \map\model\Connection();
                $connection->load($result);

                if ($connection->getFromSystem() == null && $connection->getToSystem() == null)
                {
                    $connection->delete();
                    continue;
                }

                $data = array("id"   => $connection->id,
                              "from" => ["wormhole" => $wormholes[$connection->fromWormholeID]["id"],
                                         "system"   => (isset($wormholes[$connection->fromWormholeID]["system"]))?$wormholes[$connection->fromWormholeID]["system"]["id"]:0,
                                         "type"     => $connection->getFromWormholeType()->name,
                                         "position" => $wormholes[$connection->fromWormholeID]["position"]],
                              "to"   => ["wormhole" => $wormholes[$connection->toWormholeID]["id"],
                                         "system"   => (isset($wormholes[$connection->toWormholeID]["system"]))?$wormholes[$connection->toWormholeID]["system"]["id"]:0,
                                         "type"     => $connection->getToWormholeType()->name,
                                         "position" => $wormholes[$connection->toWormholeID]["position"]],
                              "mass" => $connection->mass);

                if ($connection->frigateHole)
                    $data["frigate"] = true;
                if ($connection->allowCapitals)
                    $data["capital"] = true;
                if ($connection->normalgates)
                    $data["jumpgate"] = true;
                if ($connection->eol)
                    $data["eol"] = true;

                if ($connection->getFromSystem() != null && $connection->getToSystem() != null) {
                    if ($connection->getFromSystem()->isKSpace() && $connection->getToSystem()->isKSpace())
                        $data["nrjumps"] = $connection->getFromSystem()->getNrJumpsTo($connection->getToSystem()->id);
                }

                $connections[$connection->id] = $data;
            }
        }

        $mapData = ["wormholes" => [], "connections" => []];
        foreach ($wormholes as $whdata) {
            $mapData["wormholes"][] = $whdata;
        }
        foreach ($connections as $cdata) {
            $mapData["connections"][] = $cdata;
        }

        if (\AppRoot::doDebug())
            echo "<pre style='background-color: #eeeeee; color: #222222;'>".print_r($mapData,true)."</pre>";

        return json_encode($mapData);
    }

    function getSignatures($arguments=array(), \map\model\Map $map=null)
    {
        // Select map
        if ($map == null) {
            if (count($arguments) > 0)
                $map = \map\model\Map::findByName(array_shift($arguments));
        }
        if ($map == null)
            return "no map selected";

        // Select solarsystem
        $system = null;
        if (count($arguments) > 0)
            $system = new \map\model\SolarSystem(array_shift($arguments));
        if ($system == null)
            return "no solarsystem selected";

        $whTypes = [];
        $signatureData = [];
        foreach (\map\model\Signature::getSignaturesBySolarSystem($system->id, $map->id) as $signature)
        {
            $sigTypeName = null;
            if ($signature->sigType == "wh")
            {
                if (!isset($whTypes[$signature->sigTypeID]))
                    $whTypes[$signature->sigTypeID] = new \map\model\WormholeType($signature->sigTypeID);

                $sigTypeName = $whTypes[$signature->sigTypeID]->name;
            }

            $scannedBy = new \users\model\User($signature->scannedBy);
            $updateBy = new \users\model\User($signature->updateBy);

            $signatureData[] = [
                "id" => $signature->id,
                "sigid" => $signature->sigID,
                "type" => $signature->sigType,
                "typeid" => $signature->sigTypeID,
                "typename" => $sigTypeName,
                "strength" => $signature->signalStrength,
                "description" => $signature->sigInfo,
                "scanned" => ["age" => \Tools::getAge($signature->scandate), "by" => $scannedBy->getFullname()],
                "updated" => ["age" => \Tools::getAge($signature->updateDate), "by" => $updateBy->getFullname()]
            ];
        }

        if (\AppRoot::doDebug())
            echo "<pre style='background-color: #eeeeee; color: #222222;'>".print_r($signatureData,true)."</pre>";

        return json_encode($signatureData);
    }


    function getMove($arguments=array())
    {
        echo "<pre>".print_r($arguments,true)."</pre>";

        return "map move";
    }
}