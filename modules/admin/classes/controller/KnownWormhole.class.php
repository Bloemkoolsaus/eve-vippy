<?php
namespace admin\controller
{
	class KnownWormhole
	{
		function getOverview()
		{
			$systems = array();
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

		function getEditForm($systemID, $redirectURL=null)
		{
			$errors = array();
			$wormhole = \map\model\KnownWormhole::findBySolarSystemID($systemID);
			$system = new \map\model\SolarSystem($systemID);

			if ($redirectURL == null)
				$redirectURL = "index.php?module=admin&section=knownwormholes";

			if (\Tools::POST("save"))
			{
				$solarSystemController = new \eve\controller\SolarSystem();
				if (!$solarSystem = $solarSystemController->getSolarsystemByName(\Tools::POST("systemname")))
					$errors[] = "Solarsystem `".\Tools::POST("systemname")."` could not be found";

				if (\Tools::POST("redirecturl"))
					$redirectURL = \Tools::POST("redirecturl");

				$wormhole->systemID = $solarSystem->id;
				$wormhole->name = \Tools::POST("name");
				$wormhole->status = \Tools::POST("status");
				$wormhole->authGroupID = \User::getUSER()->getCurrentAuthGroupID();

				if (count($errors) == 0)
				{
					if ($solarSystem->id != $wormhole->systemID)
						$wormhole->delete();
					$wormhole->store();

					\AppRoot::redirect($redirectURL);
				}
			}

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("system", $system);
			$tpl->assign("wormhole", $wormhole);
			$tpl->assign("errors", $errors);
			$tpl->assign("redirecturl", $redirectURL);
			return $tpl->fetch("admin/knownwormholes/edit");
		}
	}
}
?>