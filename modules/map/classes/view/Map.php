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
        if (!$wormhole) {
            $system = $map->getHomeSystem();
            $wormhole = \map\model\Wormhole::getWormholeBySystemID($system->id, $map->id);
            if (!$wormhole) {
                $map->addHomeSystemToMap();
                $wormhole = \map\model\Wormhole::getWormholeBySystemID($system->id, $map->id);
            }
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

        /** Get notifications */
        $notifications = null;
        $cachedDate = \Session::getSession()->get(["vippy","notifications","cache"]);
        if ($cachedDate) {
            $updateDate = \Cache::memory()->get(["notifications", "update"]);
            if ($updateDate < $cachedDate)
                $notifications = \Cache::memory()->get(["notifications", \User::getUSER()->id]);
        }
        if ($notifications === null) {
            $notifications = $controller->getNotices($map);
            \Cache::memory(0)->set(["notifications", \User::getUSER()->id], $notifications);
            \Session::getSession()->set(["vippy","notifications","cache"], strtotime("now"));
        }
        if ($notifications) {
            foreach ($notifications as $note) {
                $wormhole = $map->getWormholeBySystem($note->solarSystemID);
                $title = $note->getSystem()->name;
                if ($wormhole)
                    $title = $wormhole->name." - ".$title;
                $data["notifications"][] = [
                    "id" => $note->id,
                    "wormhole" => ($wormhole)?$wormhole->id:0,
                    "type" => $note->getTypeName(),
                    "title" => "[".$title."] - ".$note->title,
                    "content" => $note->body
                ];
            }
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
        $data["fleets"] = $fleets;

        if ($map->getAuthGroup()->getConfig("fleet_warning")) {
            if (count($fleets) == 0) {
                $data["notifications"][] = [
                    "id" => "no-active-fleets",
                    "type" => "error",
                    "title" => "!!! - There are currently no active fleets registered with VIPPY - !!!",
                    "content" => "Please register your fleet with Vippy using the 'Add Fleet' button. For more information about CREST fleets, check out the help pages."
                ];
            }
        }


        /** Get map data. */
        if ($checkCache) {
            // Kijk of er iets veranderd is in de chain sinds de laatste check.
            $cacheDate = \Session::getSession()->get(["vippy","map","cache","map",$map->id]);
            if ($cacheDate && $cacheDate > strtotime(30)) {
                $mapUpdate = \Cache::memory()->get(["map", $map->id, "lastupdate"]);
                if ($mapUpdate <= $cacheDate)
                    $isCached = true;
            }
        }


        // Geen cache of cache outdated.
        if (!$isCached)
        {
            $wormholes = $controller->getWormholes($map);

            foreach ($wormholes as $key => $wh) {
                // Notifications
                foreach ($data["notifications"] as $note) {
                    if (isset($note["wormhole"]) && $note["wormhole"] == $wh["id"])
                        $wormholes[$key]["notifications"][] = $note;
                }
                // Drifters
                foreach ($controller->getDrifers($map) as $drifer) {
                    if (isset($wh["solarsystem"])) {
                        if ($wh["solarsystem"]["id"] == $drifer->solarSystemID)
                            $wormholes[$key]["drifters"] = $drifer->nrDrifters;
                    }
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
            \Session::getSession()->set(["vippy","map","cache","map",$map->id], strtotime("now"));
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

    function getRename(\map\model\map $map, $arguments=[])
    {
        $wormhole = \map\model\Wormhole::findById(array_shift($arguments));

        if (\Tools::POST("name")) {
            $wormhole->name = \Tools::POST("name");
            $wormhole->store();
            $map->setMapUpdateDate();
            exit;
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("map", $map);
        $tpl->assign("wormhole", $wormhole);
        return $tpl->fetch("map/system/rename");
    }

    function getRemove(\map\model\Map $map, $arguments=[])
    {
        $wormhole = \map\model\Wormhole::findById(array_shift($arguments));

        if (\Tools::POST("confirmed")) {
            if ($map->isAllowedAction("delete")) {
                if ($wormhole) {
                    if (\Tools::POST("connected"))
                        $map->removeConnectedWormholes($wormhole->id);
                    else
                        $wormhole->delete();
                    $map->setMapUpdateDate();
                    exit;
                }
            }
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("map", $map);
        $tpl->assign("wormhole", $wormhole);

        if (count($arguments) > 0) {
            foreach ($arguments as $arg) {
                if ($arg == "connected")
                    $tpl->assign("removeConnected", 1);
            }
        }
        return $tpl->fetch("map/map/delete");
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

    /**
     * Clear all wormholes from the map
     * @param \map\model\Map $map
     * @param array $arguments
     * @return string
     */
    function getClear(\map\model\Map $map, $arguments=[])
    {
        if ($map->isAllowedAction("delete")) {
            if (\Tools::POST("delete") == "all") {
                $map->clearChain();
                \AppRoot::redirect("map/".$map->getURL());
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