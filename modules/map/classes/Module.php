<?php
namespace map;

class Module extends \Module
{
    public $moduleName = "map";
    public $moduleTitle = "Map";

    function getContent()
    {
        $mapName = (\Tools::REQUEST("section"))?:null;
        if ($mapName) {
            $map = \map\model\Map::findByName($mapName);
            if ($map) {
                if ($map->getUserAllowed()) {
                    $view = new \map\view\Map();
                    return $view->getOverview($map);
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