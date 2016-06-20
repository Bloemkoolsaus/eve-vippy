<?php
namespace eve
{
	class Module extends \Module
	{
        public $public = false;
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

        function getAppData(\stdClass $appData)
        {
            $appData->eve = new \stdClass();
            $appData->eve->igb = \eve\model\IGB::getIGB()->isIGB();
            return $appData;
        }
	}
}
?>