<?php
namespace map\view;

class Map
{
    protected function doView($view, \map\model\Map $map, $arguments=[])
    {
        $action = (count($arguments)>0)?array_shift($arguments):null;
        $method = ($action) ? "get".ucfirst($action) : "Overview";
        if (!method_exists($view, $method)) {
            $method = "getOverview";
            if ($action)
                array_unshift($arguments, $action);
        }
        return $view->$method($map, $arguments);
    }

    function getSignatures(\map\model\Map $map, $arguments=[])
    {
        $view = new \map\view\map\Signatures();
        return $this->doView($view, $map, $arguments);
    }

    function getAnomalies(\map\model\Map $map, $arguments=[])
    {
        $view = new \map\view\map\Anomalies();
        return $this->doView($view, $map, $arguments);
    }

    function getSystem(\map\model\Map $map, $arguments=[])
    {
        $view = new \map\view\map\System();
        return $this->doView($view, $map, $arguments);
    }



    function getOverview(\map\model\Map $map=null, $arguments=[])
    {
        $system = null;
        if (count($arguments) > 0)
            $system = \map\model\SolarSystem::getSolarsystemByName(array_shift($arguments));

        if (!$system)
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
        $tpl->assign("signatureTypes", \map\model\SignatureType::findAll([], ["name"]));
        return $tpl->fetch("map/map/overview");
    }

    function getMap(\map\model\Map $map, $arguments=[])
    {
        \AppRoot::debug("----- getMap(".$map->id." - ".$map->name.") -----");
        $currentDate = date("Y-m-d H:i:s");
        $isCached = false;
        $checkCache = (\Tools::REQUEST("nocache"))?false:true;
        while (count($arguments) > 0) {
            $arg = array_shift($arguments);
            if ($arg == "nocache")
                $checkCache = false;
        }


        $controller = new \map\controller\Map();
        $data = ["map" => "cached", "notifications" => []];

        /**
         * Get notifications
         */
        foreach ($controller->getNotices($map) as $note) {
            $wormhole = $map->getWormholeBySystem($note->solarSystemID);
            $title = $note->getSystem()->name;
            if ($wormhole)
                $title = $wormhole->name." - ".$title;
            $data["notifications"][] = [
                "id" => $note->id,
                "wormhole" => $wormhole->id,
                "type" => $note->getTypeName(),
                "title" => "[".$title."] - ".$note->title,
                "content" => $note->body
            ];
        }

        if (!\User::getUSER()->getScanAlt())
        {
            $data["notifications"][] = [
                "id" => "no-scan-alt",
                "type" => "message",
                "title" => "You have not selected a scan alt",
                "content" => "Select your dedicated scanning toon (scan-alt) in your profile!"
            ];
        }

        $fleets = [];
        foreach (\crest\model\Fleet::findAll(["authgroupid" => $map->authgroupID]) as $fleet) {
            if ($fleet->active)
                $fleets[] = $fleet;
        }
        if (count($fleets) == 0) {
            $data["notifications"][] = [
                "id" => "no-active-fleets",
                "type" => "error",
                "title" => "!!! - There are currently no active fleets registered with VIPPY - !!!",
                "content" => "Vippy cannot determine character locations, auto-map wormholes or log jumped mass without an active fleet. Please register your fleet with Vippy using the 'Add Fleet' button."
            ];
        }


        /**
         * Get map data.
         */

        // Kijk of er iets veranderd is in de chain sinds de laatste check. Zo niet, is natuurlijk geen update nodig.
        if ($checkCache)
        {
            if (isset($_SESSION["vippy"]["map"]["cache"]["map"][$map->id]))
            {
                $cacheDate = $_SESSION["vippy"]["map"]["cache"]["map"][$map->id];
                if (strtotime($cacheDate) + 60 > strtotime("now")) {
                    if ($result = \MySQL::getDB()->getRow("select lastmapupdatedate as lastdate from mapwormholechains where id = ?", [$map->id]))
                    {
                        \AppRoot::debug("cache-date: " . date("Y-m-d H:i:s", strtotime($cacheDate)));
                        \AppRoot::debug("lastupdate: " . date("Y-m-d H:i:s", strtotime($result["lastdate"])));
                        if (strtotime($result["lastdate"]) < strtotime($cacheDate) - 2) {
                            \AppRoot::debug("do cache");
                            $isCached = true;
                        } else
                            \AppRoot::debug("cache out-dated.. gogogo!");
                    }
                } else
                    \AppRoot::debug("Cache is older then 1 minute");
            }
        }

        if (!$isCached)
        {
            $wormholes = $controller->getWormholes($map);
            foreach ($wormholes as $key => $wh) {
                foreach ($data["notifications"] as $note) {
                    if (isset($note["wormhole"]) && $note["wormhole"] == $wh["id"])
                        $wormholes[$key]["notifications"][] = $note;
                }
            }

            // Maak de map
            $data["map"] = [
                "settings"      => [
                    "defaultwidth"  => (int)\Config::getCONFIG()->get("map_wormhole_width"),
                    "defaultheight"  => (int)\Config::getCONFIG()->get("map_wormhole_height")
                ],
                "wormholes" 	=> $wormholes,
                "connections" 	=> $controller->getConnections($map),
                "homesystem" 	=> $map->homesystemID
            ];

            // Cache datum opslaan.
            $_SESSION["vippy"]["map"]["cache"]["map"][$map->id] = date("Y-m-d H:i:s");
        }

        // Geef de map terug
        return json_encode($data);
    }

    function getAdd(\map\model\Map $map, $arguments=[])
    {
        $errors = [];

        if (\Tools::POST("addwormhole"))
        {
            if ($map->isAllowedAction("move"))
            {
                $fromSystem = null;
                $fromSystemName = "";
                $toSystem = null;
                $toSystemName = "";

                if (isset($_POST["from"]["name"]) && strlen(trim($_POST["from"]["name"])) > 0) {
                    $names = explode("(", $_POST["from"]["name"]);
                    $fromSystemName = $names[0];
                    $fromSystem = \eve\model\SolarSystem::getSolarsystemByName($fromSystemName);
                }
                if (!$fromSystem) {
                    if (isset($_POST["from"]["id"]) && strlen(trim($_POST["from"]["id"])) > 0)
                        $fromSystem = new \eve\model\SolarSystem($_POST["from"]["id"]);
                }

                if (isset($_POST["to"]["name"]) && strlen(trim($_POST["to"]["name"])) > 0) {
                    $names = explode("(", $_POST["to"]["name"]);
                    $toSystemName = $names[0];
                    $toSystem = \eve\model\SolarSystem::getSolarsystemByName($toSystemName);
                }
                if (!$toSystem) {
                    if (isset($_POST["to"]["id"]) && strlen(trim($_POST["to"]["id"])) > 0)
                        $toSystem = new \eve\model\SolarSystem($_POST["to"]["id"]);
                }

                if ($fromSystem && $toSystem) {
                    $controller = new \map\controller\Wormhole();
                    if (!$controller->addWormhole($map, $fromSystem->id, $toSystem->id))
                        $errors[] = "Something went wrong while adding the wormhole";
                } else {
                    if (!$fromSystem)
                        $errors[] = "From system `" . $fromSystemName . "` not be found";
                    if (!$toSystem)
                        $errors[] = "From system `" . $toSystemName . "` not be found";
                }
            }

            if (count($errors) == 0)
                \AppRoot::redidrectToReferer();
        }

        $fromSystem = null;
        if (count($arguments) > 0) {
            $fromSystem = \eve\model\SolarSystem::getSolarsystemByName(array_shift($arguments));
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("fromSystem", $fromSystem);
        $tpl->assign("errors", $errors);
        $tpl->assign("map", $map);
        return $tpl->fetch("map/system/add");
    }

    function getMove(\map\model\Map $map, $arguments=[])
    {
        if ($map->isAllowedAction("move")) {
            $wormhole = new \map\model\Wormhole(\Tools::REQUEST("system"));
            $wormhole->move(\Tools::REQUEST("x"), \Tools::REQUEST("y"));
        }
        return $this->getMap($map, ["nocache"]);
    }

    function getRemove(\map\model\Map $map, $arguments=[])
    {
        if ($map->isAllowedAction("delete"))
        {
            $wormhole = \map\model\Wormhole::findById(array_shift($arguments));

            $removeConnected = false;
            if (count($arguments) > 0) {
                if ($arguments[0] == "connected")
                    $removeConnected = true;
            }
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
        if ($map->isAllowedAction("delete"))
        {
            $system = \map\model\SolarSystem::getSolarsystemByName(array_shift($arguments));
            if ($system) {
                $wormhole = \map\model\Wormhole::getWormholeBySystemID($system->id, $map->id);
                if ($wormhole) {
                    $wormhole->permanent = !$wormhole->isPermenant();
                    $wormhole->store();
                }
            }
        }
        return "done";
    }

    function getClear(\map\model\Map $map, $arguments=[])
    {
        if ($map->isAllowedAction("delete")) {
            if (\Tools::POST("delete") == "all") {
                $map->clearChain();
                \AppRoot::redirect("map/" . $map->name);
            }
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("map", $map);
        return $tpl->fetch("map/map/clear");
    }

    function getExitfinder(\map\model\Map $map, $arguments=[])
    {
        $wormholes = [];
        foreach ($map->getWormholes() as $hole) {
            if ($hole->getSolarsystem()) {
                if (!$hole->getSolarsystem()->isWSpace())
                    $wormholes[] = $hole;
            }
        }

        $solarSystem = null;
        if (count($arguments) > 0) {
            $solarSystem = \map\model\SolarSystem::getSolarsystemByName(array_shift($arguments));
        }

        $findSystem = null;
        if (\Tools::REQUEST("find"))
            $findSystem = \map\model\SolarSystem::getSolarsystemByName(\Tools::REQUEST("find"));

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("map", $map);
        $tpl->assign("wormholes", $wormholes);
        $tpl->assign("solarSystem", $solarSystem);
        $tpl->assign("findSystem", $findSystem);
        return $tpl->fetch("map/map/exitfinder");
    }
}