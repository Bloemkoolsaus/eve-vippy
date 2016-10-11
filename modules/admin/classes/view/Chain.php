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
                                                WHERE   authgroupid IN (".implode(",",\User::getUSER()->getAuthGroupsIDs()).")
                                                and     deleted = 0
                                                AND     id NOT IN (select chainid from mapwormholechains_alliances)
                                                AND     id NOT IN (select chainid from mapwormholechains_corporations)"))
        {
            foreach ($results as $result)
            {
                $chain = new \scanning\model\Chain();
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
                    $chain = new \scanning\model\Chain();
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

        $id = (count($arguments) > 0) ? array_shift($arguments) : \Tools::REQUEST("id");
        $chain = new \scanning\model\Chain($id);

        if ($chain->id == 0)    // new chain
        {
            $chain->setSetting("create-unmapped", 1);
            $chain->setSetting("count-statistics", 1);
        }
        else
        {
            if (\User::getUser()->getIsSysAdmin())
                $allowed = true;
            else {
                $allowed = false;
                foreach (\User::getUSER()->getAvailibleChains() as $userchain) {
                    if ($userchain->id == $chain->id)
                        $allowed = true;
                }
            }
            if (!$allowed)
                \AppRoot::redirect("");
        }


        $errors = array();

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


            if (\Tools::POST("control"))
            {
                foreach ($_POST["control"] as $action => $group)
                {
                    if (!is_numeric($group) || $group > 0)
                        $chain->setSetting("control-".$action, $group);
                }
            }

            if (\Tools::POST("corporations",true))
            {
                foreach ($_POST["corporations"] as $key => $id) {
                    $chain->addCorporation($id);
                }
            }
            if (\Tools::POST("alliances",true))
            {
                foreach ($_POST["alliances"] as $key => $id) {
                    $chain->addAlliance($id);
                }
            }

            if (count($errors) == 0)
            {
                $chain->store();
                \AppRoot::redirect("admin/chain");
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