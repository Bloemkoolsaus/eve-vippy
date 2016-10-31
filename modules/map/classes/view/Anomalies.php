<?php
namespace map\view;

class Anomalies
{
    function getOverview($arguments=[])
    {
        $map = \map\model\Map::findByName(array_shift($arguments));
        $system = \map\model\System::getSolarsystemByName(array_shift($arguments));

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("map", $map);
        $tpl->assign("system", $system);
        $tpl->assign("anomalies", $system->getAnomalies($map->id));
        return $tpl->fetch("map/anomaly/overview");
    }

    function getCopypaste($arguments=[])
    {
        $map = \map\model\Map::findByName(array_shift($arguments));
        $system = \map\model\System::getSolarsystemByName(array_shift($arguments));

        if (\Tools::POST("signatures"))
        {
            $view = new \map\view\Signatures();
            $view->getCopypaste($arguments, $map->id, $system->id);
            \AppRoot::redirect("map/".$map->name."/".$system->name);
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("map", $map);
        $tpl->assign("system", $system);
        return $tpl->fetch("map/anomaly/copypaste");
    }

    function getClear($arguments=[])
    {
        $map = \map\model\Map::findByName(array_shift($arguments));
        $system = \map\model\System::getSolarsystemByName(array_shift($arguments));

        foreach ($system->getAnomalies($map->id) as $anom) {
            $anom->delete();
        }

        \AppRoot::redirect("map/".$map->name."/".$system->name);
    }
}