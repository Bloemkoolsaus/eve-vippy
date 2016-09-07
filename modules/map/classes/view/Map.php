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
}