<?php
namespace admin
{
	class Module extends \Module
	{
		public $moduleName = "admin";
		public $moduleTitle = "Admin";

		function getContent()
		{
			$section = (\Tools::REQUEST("section"))?:"overview";
			$action = (\Tools::REQUEST("action"))?:"overview";


			if ($section == "clearcache")
			{
				if (\User::getUSER()->hasRight("admin","sysadmin"))
				{
					$map = new \scanning\console\Map();
					$map->cleanupCache();
					\AppRoot::redirect("index.php?module=scanning");
				}
			}

			if ($section == "chains")
			{
				if (!\User::getUSER()->getIsDirector())
					\AppRoot::redirect(APP_URL);

				\AppRoot::config("no-cache-chains", 1);

				$controller = new \admin\controller\Chain();
				if (\Tools::REQUEST("ajax"))
				{
					if ($action == "editcorps")
						return $controller->getEditCorporations();
					if ($action == "editalliances")
						return $controller->getEditAlliances();
					if ($action == "getcorpid")
					{
						$corpController = new \eve\controller\Corporation();
						$corporation = $corpController->getCorporationByName(\Tools::REQUEST("query"));
						echo $corporation->id;
						exit;
					}
					if ($action == "getallyid")
					{
						$allyController = new \eve\controller\Alliance();
						$alliance = $allyController->getAllianceByName(\Tools::REQUEST("query"));
						echo $alliance->id;
						exit;
					}
				}

				if ($action == "new" || $action == "edit")
					return $controller->getEditForm();
				else
					return $controller->getOverview();
			}

			if ($section == "changelog")
			{
				\User::getUSER()->setConfig("patchnotes", strtotime("now")+600);
                \User::getUSER()->resetCache();

				$notes = \SmartyTools::getSmarty();
				$this->moduleTitle = "Patch notes";
				$this->moduleContent = "<pre class='changelog'>".$notes->fetch("file:".getcwd()."/documents/changelog.txt")."</pre>";
			}

			if ($section == "knownwormholes")
			{
				$this->moduleTitle = "Known Wormhole Systems";

				if ($action == "new" || $action == "edit")
				{
					$kwController = new \admin\controller\KnownWormhole();
					return $kwController->getEditForm(\Tools::REQUEST("id"));
				}
				else if ($action == "delete")
				{
					$wormhole = new \admin\model\KnownWormhole(\Tools::REQUEST("id"));
					$wormhole->delete();
					\AppRoot::redirect("index.php?module=admin&section=knownwormholes");
				}
				else
				{
					$kwController = new \admin\controller\KnownWormhole();
					return $kwController->getOverview();
				}
			}

			if ($section == "authgroups")
			{
				if (count(\User::getUSER()->getAuthGroupsAdmins()) > 0 || \User::getUSER()->getIsSysAdmin())
				{
					$this->moduleTitle = "Access Control Groups";

					$controller = new \admin\controller\AuthGroup();
					if ($action == "edit" || $action == "new")
						return $controller->getEditForm(\Tools::REQUEST("id"));
					else
						$this->moduleSection = $controller->getOverviewSection();
				}
				else
					\AppRoot::redirect("index.php");
			}

			if ($section == "subscriptions")
			{
				if (\User::getUSER()->getIsSysAdmin())
				{
					if ($action == "edit" || $action == "new")
					{
						$view = new \admin\view\Subscription();
						return $view->getEditForm(\Tools::REQUEST("id"));
					}

					$controller = new \admin\controller\Subscriptions();
					$this->moduleSection = $controller->getSection();
				}
				else
					\AppRoot::redirect("index.php");
			}


			return parent::getContent();
		}

		function getCron($arguments=array())
		{
			$action = "default";
			if (isset($arguments[0]))
				$action = $arguments[0];

			echo $action." ";

			if ($action == "subscriptions")
			{
				$subscription = new \admin\controller\console\Subscription();
				$subscription->fetchWalletJournal();
				return "done";
			}

			return "unknown action";
		}
	}
}
?>