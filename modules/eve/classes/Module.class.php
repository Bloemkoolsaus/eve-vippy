<?php
namespace eve
{
	class Module extends \Module
	{
		private static $eveDB = null;

		public function __construct()
		{
			$this->moduleName = "eve";
			$this->moduleTitle = "EVE-Online";
		}

		public static function eveDB()
		{
			if (self::$eveDB === null)
				self::$eveDB = \AppRoot::getDBConfig("mysql_eve_db");

			return self::$eveDB;
		}

		function getContent()
		{
			$moduleSection = null;
			$moduleMenu = "";
			$section = (\Tools::REQUEST("section")) ?: "overview";
			$action = (\Tools::REQUEST("action")) ?: "overview";

			$this->template = \SmartyTools::getSmarty();
			$template = "index.html";

			if ($section == "overview")
			{
				$titles = new \eve\controller\Title();
				$this->template->assign("content", $titles->getTitleManagement());
			}

			if ($section == "showinfo")
			{
				$item = new \eve\view\Item();
				return $item->getShowInfo(\Tools::REQUEST("id"));
			}

			$this->template->assign("moduleTitle", $this->moduleTitle);
			return $this->template->fetch($this->getTemplate($template));
		}

		function getCron($arguments=array())
		{
			$action = "default";
			if (isset($arguments[0]))
				$action = $arguments[0];

			echo $action." ";

			if ($action == "killstats")
			{
				$killstats = new \eve\console\KillStatistics();
				$killstats->import();
				return "done";
			}
			if ($action == "fwstats")
			{
				$killstats = new \eve\console\FWStatistics();
				$killstats->import();
				return "done";
			}
			if ($action == "evecentral")
			{
				$killstats = new \eve\console\EveCentral();
				$killstats->import();
				return "done";
			}

			return "unknown action";
		}
	}
}
?>