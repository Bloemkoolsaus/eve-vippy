<?php
namespace scanning
{
	class Module extends \Module
	{
		public $moduleName = "scanning";
		public $moduleTitle = "Scanning";


		function getContent()
		{
			$section = (\Tools::REQUEST("section")) ? \Tools::REQUEST("section") : "overview";
			$action = (\Tools::REQUEST("action")) ? \Tools::REQUEST("action") : "";

			if ($section == "map" && $action == "locationtracker")
			{
				$scannerController = new \scanning\controller\Scanner();
				return $scannerController->locationTracker();
			}

			if (!\User::getUSER()->isAuthorized())
				return null;


			$tpl = \SmartyTools::getSmarty();

			// Detect system switch!
			if (\Tools::POST("currentSystem"))
			{
				$wormhole = null;
				if (\Tools::POST("currentSystem") == "current")
					$wormhole = \scanning\model\Wormhole::getWormholeBySystemID(\eve\model\IGB::getIGB()->getSolarsystemID());
				else
					$wormhole = new \scanning\model\Wormhole(\Tools::POST("currentSystem"));

				if ($wormhole !== null && $wormhole->solarSystemID > 0)
					\User::setSelectedSystem($wormhole->solarSystemID);

				\AppRoot::refresh();
			}

			if (\Tools::REQUEST("chain"))
			{
				\User::unsetSelectedSystem();
				\User::setSelectedChain(\Tools::REQUEST("chain"));
				\AppRoot::redirect("index.php?module=scanning");
			}

			if ($action == "clearchain")
			{
				$chain = \scanning\model\Chain::getCurrentChain();
				$chain->clearChain();
				\AppRoot::redirect("index.php?module=scanning");
			}

			if ($section == "map")
			{
				if ($action == "editconnection")
				{
					$connectionController = new \scanning\controller\Connection();
					return $connectionController->getEditForm(\Tools::REQUEST("from"), \Tools::REQUEST("to"));
				}

				if (\Tools::REQUEST("ajax"))
				{
					if ($action == "addwormhole")
					{
						$wormholeController = new \scanning\controller\Wormhole();
						return $wormholeController->getAddForm();
					}
					else if ($action == "addsignature" || $action == "updatesignature")
					{
						$signatureController = new \scanning\controller\Signature();
						return $signatureController->addSignature();
					}
					else if ($action == "deletesignature")
					{
						$signature = new \scanning\model\Signature(\Tools::REQUEST("id"));
						return $signature->delete();
					}
					else if ($action == "addtoknownsystems")
					{
						$mapConrtoller = new \scanning\controller\Map();
						if (\Tools::REQUEST("ajax") == "remove")
							return $mapConrtoller->removeFromKnownWormholeForm();
						else
							return $mapConrtoller->getAddKnownWormholeForm();
					}
					else if ($action == "contextmenu")
					{
						$wormhole = new \scanning\model\Wormhole(\Tools::REQUEST("id"));
						return $wormhole->showContextMenu();
					}
					else if ($action == "siglist")
					{
						$map = new \scanning\controller\Map();
						$systemController = new \eve\controller\SolarSystem();
						$system = $systemController->getSolarsystemByName(\Tools::REQUEST("system"));
						return $map->fetchSigList(\User::getSelectedChain(), $system->id);
					}
					else if ($action == "sigmap")
					{
						$scannerController = new \scanning\controller\Scanner();

						if (\Tools::REQUEST("setpermanent"))
						{
							$systemID = \scanning\Wormhole::getWormholeIdBySystem(\Tools::REQUEST("setpermanent"), \User::getSelectedChain());
							$system = new \scanning\Wormhole($systemID);
							$system->permanent = true;
							$system->store();
						}

						if (\Tools::REQUEST("unsetpermanent"))
						{
							$systemID = \scanning\Wormhole::getWormholeIdBySystem(\Tools::REQUEST("unsetpermanent"), \User::getSelectedChain());
							$system = new \scanning\Wormhole($systemID);
							$system->permanent = false;
							$system->store();
						}

						if (\Tools::REQUEST("delete"))
						{
							$chain = new \scanning\model\Chain(\User::getSelectedChain());
							if (\Tools::REQUEST("removeConnected"))
								$chain->removeConnectedWormholes(\Tools::REQUEST("delete"));
							else
								$chain->removeWormholeSystem(\Tools::REQUEST("delete"));
						}

						if (\Tools::REQUEST("move"))
						{
							$chain = new \scanning\model\Chain(\User::getSelectedChain());
							$chain->moveWormhole(\Tools::REQUEST("move"), \Tools::REQUEST("x"), \Tools::REQUEST("y"));
						}

						$map = new \scanning\controller\Map();
						return $map->fetchSigmap(\User::getSelectedChain());
					}
				}
			}

			if ($section == "sigs")
			{
				if ($action == "copypaste")
				{
					$scannerController = new \scanning\controller\Scanner();
					return $scannerController->getCopyPastSignatureForm();
				}
				if ($action == "deleteall")
				{
					$scannerController = new \scanning\controller\Scanner();
					$scannerController->deleteAllSignatures(\Tools::REQUEST("id"));
					\AppRoot::redirect("index.php?module=scanning#signatures");
				}
			}

			if ($section == "anoms")
			{
				if ($action == "copypaste")
				{
					$scannerController = new \scanning\controller\Scanner();
					return $scannerController->getCopyPasteAnomalyForm();
				}
				else if ($action == "remove")
				{
					if (\Tools::REQUEST("id") == "all") {
						foreach (\scanning\Anomaly::getSystemAnomalies(\User::getSelectedSystem()) as $anom) {
							\scanning\Anomaly::removeAnomaly($anom["id"]);
						}
					}
					else
						\scanning\Anomaly::removeAnomaly(\Tools::REQUEST("id"));

					\AppRoot::redirect("index.php?module=scanning");
				}
			}

			if ($section == "getwhdetails")
			{
				$controller = new \scanning\controller\System();
				$wormhole = new \scanning\model\Wormhole(\Tools::REQUEST("system"));

				if ($action == "gettradehubs")
					return $controller->getWHDetailsTradehubs($wormhole->solarSystemID);
				elseif ($action == "getactivity")
					return $controller->getWHDetailsActivity($wormhole->solarSystemID);
				elseif ($action == "geteffects")
					return $controller->getWHEffectsData($wormhole->solarSystemID);
				else
					return $controller->getWHDetailsPopup($wormhole->solarSystemID);
			}

			if ($section == "getconndetails")
			{
				$controller = new \scanning\controller\Connection();
				$systems = explode(",",\Tools::REQUEST("connection"));
				$connection = \scanning\model\Connection::getConnectionByWormhole($systems[0], $systems[1], \User::getSelectedChain());

				if ($connection !== null)
				{
					if ($action == "jumplog")
						return $controller->getJumplogSummary($connection->id);
					else
						return $controller->getDetailsPopup($connection->id);
				}
				else
					return "Connection not found";
			}

			if ($section == "exitfinder")
			{
				$chainView = new \scanning\view\Chain();
				return $chainView->getExitFinder();
			}

			if ($section == "overview")
			{
				$this->moduleTitle = "";
				if (count(\User::getUSER()->getAvailibleChains()) > 0)
				{
					$signatureController = new \scanning\controller\Signature();
					return $signatureController->getOverview();
				}
				else
				{
					$tpl = \SmartyTools::getSmarty();
					return $tpl->fetch("scanning/nochains");
				}
			}

			if ($section == "trackingonly")
			{
				if ($action == "showhelp")
				{
					$tpl = \SmartyTools::getSmarty();
					return $tpl->fetch(\SmartyTools::getTemplateDir("scanning")."trackingonlymode.help.html");
				}
				else if ($action == "enabletrackingonly")
				{
					$_SESSION["trackingonly"] = true;
					\AppRoot::redirect("index.php?module=scanning&section=overview");
				}
				else if ($action == "disabletrackingonly")
				{
					$_SESSION["trackingonly"] = false;
					\AppRoot::redirect("index.php?module=scanning&section=overview");
				}
			}

			if ($section == "activepilots")
			{
				$mapController = new \scanning\controller\Map();
				return $mapController->getActivePilots();
			}

			if ($section == "maplegend")
			{
				$mapController = new \scanning\controller\Map();
				return $mapController->getLegend();
			}

			if ($section == "snaptogrid")
			{
				$mapController = new \scanning\controller\Map();
				return $mapController->snapToGrid();
			}


			return parent::getContent();
		}

		function getCron($arguments=array())
		{
			$action = "default";
			if (isset($arguments[0]))
				$action = $arguments[0];

			echo $action." ";

			if ($action == "cleanup")
			{
				$map = new \scanning\console\Map();
				$map->cleanupCache();
				$map->doMaintenance();
				return "done";
			}

			return "unknown action";
		}
	}
}
?>