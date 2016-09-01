<?php
namespace map\view;

class Knownwormhole
{
    function getAdd($arguments=[])
    {
        $system = \map\model\SolarSystem::getSolarsystemByName(array_shift($arguments));

        if (\Tools::POST("name"))
        {
            $known = $system->getKnownSystem();
            if (!$known) {
                $known = new \map\model\KnownWormhole();
                $known->solarSystemID = $system->id;
                $known->authGroupID = \User::getUSER()->getCurrentAuthGroupID();
            }

            $known->name = \Tools::POST("name");
            $known->status = \Tools::POST("status");
            $known->store();

            \AppRoot::redidrectToReferer();
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("system", $system);
        return $tpl->fetch("map/system/known/add");
    }

    function getRemove($arguments=[])
    {
        $system = \map\model\SolarSystem::getSolarsystemByName(array_shift($arguments));

        if (\Tools::POST("confirmed"))
        {
            if ($system->getKnownSystem())
                $system->getKnownSystem()->delete();

            \AppRoot::redidrectToReferer();
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("system", $system);
        return $tpl->fetch("map/system/known/remove");
    }
}