<?php
namespace map\view;

class Knownwormhole
{
    function getEdit($arguments=[])
    {
        $system = \map\model\SolarSystem::getSolarsystemByName(array_shift($arguments));
        $known = $system->getKnownSystem();

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("system", $system);
        $tpl->assign("known", $known);
        return $tpl->fetch("map/system/knownwormhole");
    }

    function getSave($arguments=[])
    {
        $system = \map\model\SolarSystem::getSolarsystemByName(array_shift($arguments));

        $known = $system->getKnownSystem();
        if (!$known) {
            $known = new \map\model\KnownWormhole();
            $known->solarSystemID = $system->id;
            $known->authGroupID = \User::getUSER()->getCurrentAuthGroupID();
        }

        $known->name = \Tools::POST("name");
        $known->status = \Tools::POST("status");
        $known->store();

        foreach (\User::getUSER()->getAvailibleChains() as $map) {
            $map->setMapUpdateDate();
        }

        return "stored";
    }

    function getRemove($arguments=[])
    {
        $system = \map\model\SolarSystem::getSolarsystemByName(array_shift($arguments));

        if (\Tools::POST("confirmed")) {
            if ($system->getKnownSystem())
                $system->getKnownSystem()->delete();

            foreach (\User::getUSER()->getAvailibleChains() as $map) {
                $map->setMapUpdateDate();
            }
        }

        return "removed";
    }
}