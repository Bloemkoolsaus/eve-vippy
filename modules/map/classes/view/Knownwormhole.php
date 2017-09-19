<?php
namespace map\view;

class Knownwormhole
{
    function getEdit(\map\model\Map $map, $arguments=[])
    {
        if (!$map->getUserAllowed())
            return "Oops! Not allowed.";

        $system = \map\model\SolarSystem::getSolarsystemByName(array_shift($arguments));
        $known = $system->getKnownSystem($map->authgroupID);

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("system", $system);
        $tpl->assign("known", $known);
        return $tpl->fetch("map/system/knownwormhole");
    }

    function getSave(\map\model\Map $map, $arguments=[])
    {
        if (!$map->getUserAllowed())
            return "Oops! Not allowed.";

        $system = \map\model\SolarSystem::getSolarsystemByName(array_shift($arguments));
        $known = $system->getKnownSystem($map->authgroupID);
        if (!$known) {
            $known = new \map\model\KnownWormhole();
            $known->solarSystemID = $system->id;
            $known->authGroupID = $map->authgroupID;
        }

        if (\Tools::POST("name")) {
            $known->name = \Tools::POST("name");
            $known->status = \Tools::POST("status");
            $known->store();
        } else
            $known->delete();

        $map->setMapUpdateDate();
        return "stored";
    }

    function getRemove(\map\model\Map $map, $arguments=[])
    {
        if (!$map->getUserAllowed())
            return "Oops! Not allowed.";

        $system = \map\model\SolarSystem::getSolarsystemByName(array_shift($arguments));
        if (\Tools::POST("confirmed")) {
            $known = $system->getKnownSystem($map->authgroupID);
            if ($known)
                $known->delete();
        }
        $map->setMapUpdateDate();
        return "removed";
    }
}