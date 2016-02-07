<?php
namespace admin\controller
{
	class Chain extends \scanning\model\Chain
	{
		public function getOverview()
		{
			if (!\User::getUSER()->isAdmin())
				\AppRoot::redirect(APP_URL);

			$chains = array();
			$adminChains = array();

			if (\Tools::REQUEST("action") == "delete")
			{
				$chain = new \scanning\model\Chain(\Tools::REQUEST("id"));
				$chain->deleted = true;
				$chain->store();
				\AppRoot::redirect("index.php?module=admin&section=chains");
			}

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

		public function getEditForm()
		{
			if (!\User::getUSER()->isAdmin())
				\AppRoot::redirect(APP_URL);


			$chain = new \scanning\model\Chain(\Tools::REQUEST("id"));

			if (\User::getUser()->getIsSysAdmin())
				$allowed = true;
			else
			{
				$allowed = false;
				foreach (\User::getUSER()->getAvailibleChains() as $userchain)
				{
					if ($userchain->id == $chain->id)
						$allowed = true;
				}
				if (\Tools::REQUEST("action") == "new")
					$allowed = true;
			}

			if (!$allowed)
				\AppRoot::redirect(APP_URL);

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
                $chain->countInStats = (\Tools::POST("countinstats"))?1:0;
                $chain->directorsOnly = (\Tools::POST("dironly"))?1:0;

                if ($chain->getAuthGroup() != null && $chain->getAuthGroup()->getConfig('wh_naming_numeric') > 0)
                    $chain->autoNameNewWormholes = \Tools::POST("autonamewhs");
                else
                    $chain->autoNameNewWormholes = (\Tools::POST("autonamewhs"))?1:0;

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
					\AppRoot::redirect("index.php?module=admin&section=chains");
				}
			}


			$corporations = "";
			$alliances = "";

			if (\Tools::REQUEST("action") == "new" && !\Tools::POST("id"))
			{
                foreach (\User::getUSER()->getAuthGroups() as $authgroup) {
                    $chain->authgroupID = $authgroup->id;
                    break;
                }
                foreach ($chain->getAuthGroup()->getCorporations() as $corp) {
                    $chain->addCorporation($corp->id);
                }
                foreach ($chain->getAuthGroup()->getAlliances() as $alliance) {
                    $chain->addAlliance($alliance->id);
                }
			}

            foreach ($chain->getCorporations() as $corporation) {
                $corporations .= "[".$corporation->id."],";
            }
            foreach ($chain->getAlliances() as $alliance) {
                $alliances .= "[".$alliance->id."],";
            }

			$authGroups = array();

			if (\User::getUSER()->getIsSysAdmin())
			{
				foreach (\admin\model\AuthGroup::getAuthGroups() as $group) {
                    $authGroups[$group->id] = $group;
				}
			}
			else
			{
				foreach (\User::getUSER()->getAuthGroupsAdmins() as $group) {
                    $authGroups[$group->id] = $group;
				}
			}

			if (count($authGroups) == 1)
				$chain->authgroupID = current($authGroups)->id;


			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("chain", $chain);
			$tpl->assign("authgroups", $authGroups);
			$tpl->assign("corporations", $corporations);
			$tpl->assign("alliances", $alliances);
            $tpl->assign("namingschemes", \map\model\NamingScheme::findAll());

			if (count($errors) > 0)
				$tpl->assign("errors", $errors);

			return $tpl->fetch("admin/chain/edit");
		}

		public function getEditCorporations()
		{
			$ids = array();
			foreach (explode(",",\Tools::REQUEST("corporationids")) as $id)
			{
				$id = str_replace("[","",$id);
				$id = str_replace("]","",$id);
				if (strlen(trim($id)) > 0 && is_numeric($id) && $id != \Tools::REQUEST("remove"))
					$ids[] = $id;
			}

			if (\Tools::REQUEST("add"))
			{
				if (strlen(trim(\Tools::REQUEST("add"))) > 0 && is_numeric(\Tools::REQUEST("add")))
					$ids[] = \Tools::REQUEST("add");
			}

			$corporations = array();
			if ($results = \MySQL::getDB()->getRows("SELECT	c.id, c.name, a.name AS alliance
													FROM	corporations c
														LEFT JOIN alliances a ON a.id = c.allianceid
													WHERE	c.id IN (".implode(",",$ids).")
													ORDER BY a.name, c.name"))
			{
				foreach ($results as $result)
				{
					$corporations[] = array("id"		=> $result["id"],
											"name"		=> $result["name"],
											"alliance"	=> $result["alliance"]);
				}
			}
			return json_encode($corporations,true);
		}

		public function getEditAlliances()
		{
			$ids = array();
			foreach (explode(",",\Tools::REQUEST("allianceids")) as $id)
			{
				$id = str_replace("[","",$id);
				$id = str_replace("]","",$id);
				if (strlen(trim($id)) > 0 && is_numeric($id) && $id != \Tools::REQUEST("remove"))
					$ids[] = $id;
			}

			if (\Tools::REQUEST("add"))
			{
				if (strlen(trim(\Tools::REQUEST("add"))) > 0 && is_numeric(\Tools::REQUEST("add")))
					$ids[] = \Tools::REQUEST("add");
			}

			$corporations = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM alliances WHERE id IN (".implode(",",$ids).")"))
			{
				foreach ($results as $result)
				{
					$corporations[] = array("id"	=> $result["id"],
											"name"	=> $result["name"]);
				}
			}
			return json_encode($corporations,true);
		}
	}
}
?>