<?php
namespace admin\view;

class Knownwormholes
{
    function getOverview($arguments=[])
    {
        $systems = [];
        if ($results = \MySQL::getDB()->getRows("SELECT	k.*, s.solarsystemname
                                                FROM	map_knownwormhole k
                                                    INNER JOIN ".\eve\Module::eveDB().".mapsolarsystems s ON s.solarsystemid = k.solarsystemid
                                                WHERE	k.authgroupid IN (".implode(",",\User::getUSER()->getAuthGroupsIDs()).")
                                                ORDER BY s.solarsystemname"))
        {
            foreach ($results as $result)
            {
                $wormhole = new \map\model\KnownWormhole();
                $wormhole->load($result);
                $systems[] = $wormhole;
            }
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("systems", $systems);
        return $tpl->fetch("admin/knownwormholes/overview");
    }

    function getNew($arguments=[])
    {
        return $this->getEdit();
    }

    function getEdit($arguments=[])
    {
        $errors = array();
        $wormhole = \map\model\KnownWormhole::findBySolarSystemID(array_shift($arguments));

        if (\Tools::POST("save"))
        {
            $solarSystem = \map\model\SolarSystem::getSolarsystemByName(\Tools::POST("systemname"));
            if (!$solarSystem)
                $errors[] = "Solarsystem `".\Tools::POST("systemname")."` could not be found";

            if (count($errors) == 0)
            {
                if (!$wormhole)
                    $wormhole = new \map\model\KnownWormhole();

                $wormhole->solarSystemID = $solarSystem->id;
                $wormhole->name = \Tools::POST("name");
                $wormhole->status = \Tools::POST("status");
                $wormhole->authGroupID = \User::getUSER()->getCurrentAuthGroupID();

                if ($solarSystem->id != $wormhole->solarSystemID)
                    $wormhole->delete();
                $wormhole->store();

                \AppRoot::redirect("admin/knownwormholes");
            }
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("wormhole", $wormhole);
        $tpl->assign("errors", $errors);
        return $tpl->fetch("admin/knownwormholes/edit");
    }
}