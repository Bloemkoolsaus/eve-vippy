<?php
namespace map\view;

class Map
{
    function getOverview(\map\model\Map $map, $arguments=[])
    {
        if (count($arguments) > 0)
            $system = \map\model\System::getSolarsystemByName(array_shift($arguments));
        else
            $system = $map->getHomeSystem();

        $wormhole = \map\model\Wormhole::getWormholeBySystemID($system->id, $map->id);
        if (!isset($wormhole)) {
            $system = $map->getHomeSystem();
            $wormhole = \map\model\Wormhole::getWormholeBySystemID($system->id, $map->id);
        }

        \AppRoot::title($wormhole->name);
        \AppRoot::title($system->name);
        \AppRoot::title($map->name);

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("map", $map);
        $tpl->assign("system", $system);
        $tpl->assign("wormhole", $wormhole);
        return $tpl->fetch("map/map/overview");
    }

    function getMap(\map\model\Map $map, $arguments=[])
    {
        \AppRoot::debug("----- getMap(".$map->id." - ".$map->name.") -----");
        $currentDate = date("Y-m-d H:i:s");
        $checkCache = (\Tools::REQUEST("nocache"))?false:true;

        // Kijk of er iets veranderd is in de chain sinds de laatste check. Zo niet, is natuurlijk geen update nodig.
        if ($checkCache)
        {
            $cacheDate = (isset($_SESSION["vippy_cachedate_map"])) ? $_SESSION["vippy_cachedate_map"] : 0;
            if ($results = \MySQL::getDB()->getRows("select lastmapupdatedate as lastdate from mapwormholechains where id = ?", [$map->id]))
            {
                foreach ($results as $result)
                {
                    \AppRoot::debug("cache-date: ".date("Y-m-d H:i:s", strtotime($cacheDate)));
                    \AppRoot::debug("lastupdate: ".date("Y-m-d H:i:s", strtotime($result["lastdate"])));

                    if (strtotime($cacheDate)+60 > strtotime("now")) {
                        if (strtotime($result["lastdate"]) < strtotime($cacheDate)-2) {
                            \AppRoot::debug("do cache");
                            return "cached";
                        } else {
                            \AppRoot::debug("cache out-dated.. gogogo!");
                            break;
                        }
                    } else {
                        \AppRoot::debug("Cache is older then 1 minute");
                        break;
                    }
                }
            }
        }

        // Maak de map
        $controller = new \map\controller\Map();
        $map = [
            "wormholes" 	=> $controller->getWormholes($map),
            "connections" 	=> $controller->getConnections($map),
            "homesystem" 	=> $map->homesystemID,
            "notices"		=> $controller->getNotices($map)
        ];

        // Cache datum opslaan.
        $_SESSION["vippy_cachedate_map"] = $currentDate;

        // Geef de map terug
        return json_encode($map);
    }

    function getSignatures(\map\model\Map $map, $arguments=[])
    {
        if (count($arguments)) {
            if ($arguments[0] == "store") {
                return $this->storeSignature($map);
            }
            if ($arguments[0] == "delete") {
                $signature = \map\model\Signature::findById($arguments[1]);
                if ($signature)
                    return $this->deleteSignature($signature);
            }
        }

        \AppRoot::debug("----- getSignatures(".$map->id." - ".$map->name.") -----");
        $currentDate = date("Y-m-d H:i:s");
        $checkCache = (\Tools::REQUEST("nocache"))?false:true;

        $solarSystem = (count($arguments) > 0) ? \map\model\System::getSolarsystemByName(array_shift($arguments)) : null;
        if (!$solarSystem)
            $solarSystem = $map->getHomeSystem();

        // Kijk of er iets veranderd is in de chain sinds de laatste check. Zo niet, is natuurlijk geen update nodig.
        if ($checkCache)
        {
            // Bestaat er wel een cache?
            iF (isset($_SESSION["vippy"]["map"]["cache"]["signatures"][$solarSystem->id]))
            {
                $cacheDate = $_SESSION["vippy"]["map"]["cache"]["signatures"][$solarSystem->id];
                if ($result = \MySQL::getDB()->getRow("	SELECT	MAX(s.updatedate) AS lastdate
                                                        FROM	mapsignatures s
                                                            INNER JOIN mapwormholechains c ON c.authgroupid = s.authgroupid
                                                        WHERE	c.id = ?"
                                                , [$map->id]))
                {
                    \AppRoot::debug("cache-date: " . date("Y-m-d H:i:s", strtotime($cacheDate)));
                    \AppRoot::debug("lastupdate: " . date("Y-m-d H:i:s", strtotime($result["lastdate"])));

                    if (strtotime($cacheDate) > strtotime($result["lastdate"])) {
                        if (strtotime($cacheDate) > mktime(date("H"), date("i") - 1, date("s"), date("m"), date("d"), date("Y"))) {
                            \AppRoot::debug("do cache");
                            return "cached";
                        }
                    }
                }
            }
        }

        $signatures = [];
        foreach (\map\model\Signature::findAll(["deleted" => 0, "solarsystemid" => $solarSystem->id, "authgroupid" => $map->authgroupID]) as $sig)
        {
            $sigData = [
                "id" => $sig->id,
                "sigid" => $sig->sigID,
                "type" => $sig->sigType,
                "info" => $sig->sigInfo,
                "wormhole" => null,
                "scanage" => \Tools::getAge($sig->scanDate),
                "scanuser" => $sig->getScannedByUser()->getFullName(),
                "updateage" => \Tools::getAge($sig->updateDate),
                "updateuser" => $sig->getUpdatedByUser()->getFullName()
            ];

            if ($sig->isWormhole()) {
                $sigData["wormhole"] = [
                    "type" => $sig->getWormholeType()->name,
                    "desto" => $sig->getWormholeType()->getDestinationclass()->tag
                ];
            }
            $signatures[] = $sigData;
        }

        $_SESSION["vippy"]["map"]["cache"]["signatures"][$solarSystem->id] = date("Y-m-d H:i:s");
        return json_encode($signatures);
    }

    function getMove(\map\model\Map $map, $arguments=[])
    {
        $wormhole = new \map\model\Wormhole(\Tools::REQUEST("system"));
        $wormhole->move(\Tools::REQUEST("x"), \Tools::REQUEST("y"));
        return $this->getMap($map);
    }

    function getRemove(\map\model\Map $map, $arguments=[])
    {
        $system = \map\model\System::getSolarsystemByName(array_shift($arguments));
        $removeConnected = false;
        if (count($arguments) > 0) {
            if ($arguments[0] == "connected")
                $removeConnected = true;
        }
        if ($system) {
            $wormhole = \map\model\Wormhole::getWormholeBySystemID($system->id, $map->id);
            if ($wormhole) {
                if ($removeConnected)
                    $map->removeConnectedWormholes($wormhole->id);
                else
                    $wormhole->delete();
            }
        }
        return "done";
    }

    function getPermanent(\map\model\Map $map, $arguments=[])
    {
        $system = \map\model\System::getSolarsystemByName(array_shift($arguments));
        if ($system) {
            $wormhole = \map\model\Wormhole::getWormholeBySystemID($system->id, $map->id);
            if ($wormhole) {
                $wormhole->permanent = !$wormhole->isPermenant();
                $wormhole->store();
            }
        }
        return "done";
    }

    private function storeSignature(\map\model\Map $map)
    {
        \AppRoot::debug("storeSignature(".\Tools::REQUEST("systemid").")");

        $signature = null;
        $solarsystem = \map\model\SolarSystem::getSolarsystemByName(\Tools::REQUEST("systemid"));
        if (!$solarsystem)
            return false;

        if (\Tools::REQUEST("id"))
            $signature = \map\model\Signature::findById(\Tools::REQUEST("id"));
        if (!$signature) {
            $signature = \map\model\Signature::findOne([
                "sigid" => \Tools::REQUEST("sigid"),
                "solarsystemid" => $solarsystem->id,
                "authgroupid" => $map->authgroupID
            ]);
        }
        if (!$signature)
            $signature = new \map\model\Signature();

        $signature->sigID = \Tools::REQUEST("sigid");
        $signature->sigType = \Tools::REQUEST("type");
        $signature->sigInfo = \Tools::REQUEST("info");
        $signature->solarSystemID = $solarsystem->id;
        $signature->authGroupID = $map->authgroupID;

        $signature->typeID = 0;
        $whtype = \map\model\WormholeType::findByName(\Tools::REQUEST("whtype"));
        if ($whtype)
            $signature->typeID = $whtype->id;

        $controller = new \map\controller\Signature();
        $controller->storeSignature($map, $signature);
        return "stored";
    }

    private function deleteSignature(\map\model\Signature $signature)
    {
        $signature->delete();
        return "deleted";
    }
}