<?php
namespace admin\view;

class Chain
{
    function getOverview($arguments=[])
    {
        \AppRoot::config("no-cache-chains", 1);
        if (!\User::getUSER()->isAdmin())
            \AppRoot::redirect("");

        $chains = array();
        $adminChains = array();

        $chains = array();
        foreach (\User::getUSER()->getAvailibleChains(false) as $chain)
        {
            if ($chain->getAllowedAdmin())
                $chains[] = $chain;
        }

        // Chains toevoegen die GEEN alliances/corporations hebben.
        if ($results = \MySQL::getDB()->getRows("SELECT *
                                                FROM    mapwormholechains
                                                WHERE   authgroupid IN (".implode(",",\User::getUSER()->getAuthGroupsIDs(false)).")
                                                and     deleted = 0
                                                AND     id NOT IN (select chainid from mapwormholechains_alliances)
                                                AND     id NOT IN (select chainid from mapwormholechains_corporations)"))
        {
            foreach ($results as $result)
            {
                $chain = new \map\model\Map();
                $chain->load($result);
                $chains[] = $chain;
            }
        }



        if (\User::getUSER()->getIsSysAdmin())
        {
            if ($results = \MySQL::getDB()->getRows("SELECT *
                                                    FROM 	mapwormholechains
                                                    WHERE 	deleted = 0
                                                    AND 	id NOT IN (".implode(",", \User::getUSER()->getAvailibleChainIDs()).")
                                                    and     deleted = 0
                                                    ORDER BY authgroupid, prio, id, name ASC"))
            {
                foreach ($results as $result)
                {
                    $chain = new \map\model\Map();
                    $chain->load($result);
                    $adminChains[] = $chain;
                }
            }
        }


        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("chains", $chains);
        $tpl->assign("adminchains", $adminChains);

        if (\User::getUSER()->getIsSysAdmin())
            $tpl->assign("sysadmin",1);

        return $tpl->fetch("admin/chain/overview");
    }

    function getDelete($arguments=[])
    {
        $id = (count($arguments) > 0) ? array_shift($arguments) : \Tools::REQUEST("id");
        $chain = new \scanning\model\Chain($id);
        $chain->deleted = true;
        $chain->store();
        \AppRoot::redirect("admin/chain");
    }

    function getNew($arguments=[])
    {
        return $this->getEdit($arguments);
    }

    function getEdit($arguments=[])
    {
        \AppRoot::config("no-cache-chains", 1);
        if (!\User::getUSER()->isAdmin())
            \AppRoot::redirect("");

        $errors = [];
        $id = (count($arguments) > 0) ? array_shift($arguments) : \Tools::REQUEST("id");
        $chain = new \scanning\model\Chain($id);
        \AppRoot::title("Admin Map");
        \AppRoot::title($chain->name);

        if ($chain->id == 0) {  // new chain
            $chain->setSetting("create-unmapped", 1);
            $chain->setSetting("count-statistics", 1);
            $chain->setSetting("auto-expiry", 1);
        }

        if (\Tools::REQUEST("deletealliance")) {
            $alliance = new \eve\model\Alliance(\Tools::REQUEST("deletealliance"));
            $chain->deleteAlliance($alliance);
            $chain->store();
            \AppRoot::redirect("admin/chain/edit/".$chain->id);
        }

        if (\Tools::REQUEST("deletecorporation")) {
            $corporation = new \eve\model\Corporation(\Tools::REQUEST("deletecorporation"));
            $chain->deleteCorporation($corporation);
            $chain->store();
            \AppRoot::redirect("admin/chain/edit/".$chain->id);
        }

        if (\Tools::REQUEST("deleteaccesslist")) {
            $accesslist = new \admin\model\AccessList(\Tools::REQUEST("deleteaccesslist"));
            $chain->deleteAccessList($accesslist);
            $chain->store();
            \AppRoot::redirect("admin/chain/edit/".$chain->id);
        }

        if (\Tools::POST("store"))
        {
            $solarSystemController = new \eve\controller\SolarSystem();
            if (!$solarSystem = $solarSystemController->getSolarsystemByName(\Tools::POST("homesystem")))
                $errors[] = "Home-system `".\Tools::POST("homesystem")."` not found";

            $chain->name = \Tools::POST("name");
            $chain->authgroupID = \Tools::POST("authgroup");
            $chain->homesystemID = $solarSystem->id;
            $chain->systemName = (\Tools::POST("homesystemname"))?\Tools::POST("homesystemname"):$solarSystem->name;
            $chain->prio = \Tools::POST("sortorder");

            $chain->clearSettings();
            if (\Tools::POST("settings")) {
                foreach ($_POST["settings"] as $var => $val) {
                    if ($val > 0)
                        $chain->setSetting($var, $val);
                }
            }

            if (\Tools::POST("control")) {
                foreach ($_POST["control"] as $action => $group) {
                    if (!is_numeric($group) || $group > 0)
                        $chain->setSetting("control-".$action, $group);
                }
            }

            if (!$chain->id) {
                // Nieuwe chain, alle corp/alliances toevoegen!
                foreach ($chain->getAuthGroup()->getAlliances() as $alliance) {
                    $chain->addAlliance($alliance);
                    foreach ($alliance->getCorporations() as $corp) {
                        $chain->addCorporation($corp);
                    }
                }
                foreach ($chain->getAuthGroup()->getCorporations() as $corp) {
                    $chain->addCorporation($corp);
                }
            }

            if (\Tools::POST("alliance")) {
                $alliance = new \eve\model\Alliance(\Tools::POST("alliance"));
                $chain->addAlliance($alliance);
            }
            if (\Tools::POST("corporation")) {
                $corporation = new \eve\model\Corporation(\Tools::POST("corporation"));
                $chain->addCorporation($corporation);
            }
            if (\Tools::POST("accesslist")) {
                $accesslist = new \admin\model\AccessList(\Tools::POST("accesslist"));
                $chain->addAccessList($accesslist);
            }

            // No errors, store chain!
            if (count($errors) == 0) {
                $chain->store();
                \AppRoot::redirect("admin/chain/edit/".$chain->id);
            }
        }


        $authGroups = array();
        if (\User::getUSER()->getIsSysAdmin()) {
            foreach (\admin\model\AuthGroup::getAuthGroups() as $group) {
                $authGroups[$group->id] = $group;
            }
        } else {
            foreach (\User::getUSER()->getAuthGroupsAdmins() as $group) {
                $authGroups[$group->id] = $group;
            }
        }

        if (count($authGroups) == 1)
            $chain->authgroupID = current($authGroups)->id;


        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("chain", $chain);
        $tpl->assign("authgroups", $authGroups);
        $tpl->assign("namingschemes", \map\model\NamingScheme::findAll());

        if (count($errors) > 0)
            $tpl->assign("errors", $errors);

        return $tpl->fetch("admin/chain/edit");
    }
}