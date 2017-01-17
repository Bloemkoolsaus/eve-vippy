<?php
namespace map\view\map;

class Anomalies
{
    function getOverview(\map\model\Map $map, $arguments=[])
    {
        $system = \map\model\SolarSystem::getSolarsystemByName(array_shift($arguments));

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("map", $map);
        $tpl->assign("system", $system);
        $tpl->assign("anomalies", $system->getAnomalies($map->id));
        return $tpl->fetch("map/anomaly/overview");
    }

    function getCopypaste(\map\model\Map $map, $arguments=[])
    {
        $system = \map\model\SolarSystem::getSolarsystemByName(array_shift($arguments));

        if (\Tools::POST("signatures")) {
            $view = new \map\view\Signatures();
            $view->getCopypaste($map, $arguments, $map->id, $system->id);
            \AppRoot::redirect("map/".$map->name."/".$system->name);
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("map", $map);
        $tpl->assign("system", $system);
        return $tpl->fetch("map/anomaly/copypaste");
    }

    function getDelete(\map\model\Map $map, $arguments=[])
    {
        $anom = new \map\model\Anomaly(array_shift($arguments));
        if ($anom) {
            $system = new \map\model\SolarSystem($anom->solarSystemID);
            $anom->delete();
            return $this->getOverview($map, [$system->name]);
        }
        return "oops";
    }

    function getClear(\map\model\Map $map, $arguments=[])
    {
        $system = \map\model\SolarSystem::getSolarsystemByName(array_shift($arguments));
        foreach ($system->getAnomalies($map->id) as $anom) {
            $anom->delete();
        }
        return $this->getOverview($map, [$system->name]);
    }
}