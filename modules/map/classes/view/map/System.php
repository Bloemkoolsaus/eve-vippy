<?php
namespace map\view\map;

class System
{
    function getEdit(\map\model\Map $map, $arguments=[])
    {
        $system = \map\model\SolarSystem::getSolarsystemByName(array_shift($arguments));
        //$map = \map\model\Map::findByName(array_shift($arguments));
        $wormhole = \map\model\Wormhole::getWormholeBySystemID($system->id, $map->id);

        $wormhole->name = \Tools::POST("whname");
        $wormhole->status = \Tools::POST("whstatus");
        $wormhole->store();

        if (\Tools::POST("notes"))
            $system->setNotes(\Tools::POST("notes"), $map->authgroupID);
        else
            $system->resetNotes($map->authgroupID);

        $map->setMapUpdateDate();
        \AppRoot::redirect("map/".$map->name."/".$system->name);
    }

    function getMarkscanned(\map\model\Map $map, $arguments=[])
    {
        $system = \map\model\SolarSystem::getSolarsystemByName(array_shift($arguments));
        $wormhole = \map\model\Wormhole::getWormholeBySystemID($system->id, $map->id);
        $wormhole->markFullyScanned();

        \AppRoot::redirect("map/".$map->name."/".$system->name);
    }
}