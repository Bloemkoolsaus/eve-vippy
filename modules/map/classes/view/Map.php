<?php
namespace map\view;

class Map
{
    function getOverview(\map\model\Map $map, $arguments=[])
    {
        if (count($arguments) > 0)
            $system = \map\model\System::getSolarsystemByName(array_shift($arguments));
        else
            $system = \map\model\System::getCurrentSystem();

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
}