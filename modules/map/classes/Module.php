<?php
namespace map;

class Module extends \Module
{
    public $moduleName = "map";
    public $moduleTitle = "Map";

    function getContent()
    {
        $arguments = [];
        foreach (explode(",", \Tools::REQUEST("arguments")) as $arg) {
            if (strlen(trim($arg)) > 0)
                $arguments[] = $arg;
        }

        $mapName = (\Tools::REQUEST("section"))?:null;
        if ($mapName)
        {
            foreach (\User::getUSER()->getAvailibleChains() as $chain)
            {
                if (strtolower($chain->name) == strtolower($mapName))
                {
                    $map = new \map\model\Map($chain->id);
                    $_GET["chainid"] = $map->id;

                    $view = new \map\view\Map();
                    $action = (count($arguments)>0)?array_shift($arguments):null;
                    $method = ($action) ? "get".ucfirst($action) : "Overview";
                    if (!method_exists($view, $method)) {
                        $method = "getOverview";
                        if ($action)
                            array_unshift($arguments, $action);
                    }
                    return $view->$method($map, $arguments);
                }
            }
        }
        else
        {
            // Geen map gekozen. Pak eerste map
            if (count(\User::getUSER()->getAvailibleChains()) > 0) {
                foreach (\User::getUSER()->getAvailibleChains() as $chain) {
                    \AppRoot::redirect("map/" . $chain->name);
                }
            } else {
                $tpl = \SmartyTools::getSmarty();
                return $tpl->fetch("map/map/nomap");
            }
        }


        return parent::getContent();
    }

    function getAppData(\stdClass $appData)
    {
        $appData->map = new \stdClass();
        $appData->map->trackingMode = (isset($_SESSION["trackingonly"]) && $_SESSION["trackingonly"] === true) ? true : false;
        return $appData;
    }

    function doMaintenance()
    {
        $console = new \map\console\Map();
        $console->cleanupSignatures();
        $console->cleanupWormholes();
        return true;
    }
}