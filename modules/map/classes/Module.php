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
            $map = \map\model\Map::findByName($mapName);
            if ($map)
            {
                if ($map->getUserAllowed())
                {
                    $view = new \map\view\Map();
                    $action = (count($arguments)>0)?array_shift($arguments):"overview";
                    $method = "get".ucfirst($action);
                    if (!method_exists($view, $method)) {
                        $method = "getOverview";
                        array_unshift($arguments, $action);
                    }
                    return $view->$method($map);
                }
            }
        }

        $view = $this->getView();
        if ($view)
            return $view;

        return "Hai!";
    }

    function getAppData(\stdClass $appData)
    {
        $appData->map = new \stdClass();
        $appData->map->trackingMode = (isset($_SESSION["trackingonly"]) && $_SESSION["trackingonly"] === true) ? true : false;
        return $appData;
    }
}