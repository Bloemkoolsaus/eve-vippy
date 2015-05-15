<?php
namespace scanning\console
{
	class Map
	{
		function cleanupCache()
		{
			\Tools::deleteDir("documents/cache");
			\Tools::deleteDir("documents/statistics");

			\MySQL::getDB()->doQuery("TRUNCATE mapsolarsysteminfocache");
			\MySQL::getDB()->doQuery("TRUNCATE mapwormholecharacterlocations");
			\MySQL::getDB()->doQuery("TRUNCATE mapnrofjumps");
			\MYSQL::getDB()->doQuery("DELETE FROM mapwormholejumplog WHERE jumptime < '".date("Y-m-d", mktime(0,0,0,date("m"),date("d")-5,date("Y")))."'");

			return true;
		}

		function doMaintenance()
		{
			\AppRoot::setMaxExecTime(9999);
			$this->cleanupSignatures();
			$this->cleanupWormholes();
		}

		function cleanupSignatures()
		{
			// Oude signatures opruimen.
			$cleanupDate = date("Y-m-d H:i:s", mktime(date("H")-1,date("i"),date("s"),date("m"),date("d")-3,date("Y")));
			while ($results = \MySQL::getDB()->getRows("SELECT * FROM mapsignatures WHERE updatedate < ? AND deleted = 0 AND sigtype != 'pos'", array($cleanupDate)))
			{
				foreach ($results as $result)
				{
					$signature = new \scanning\model\Signature();
					$signature->load($result);
					$signature->delete();
				}
			}

			$cleanupDate = date("Y-m-d H:i:s", mktime(0,0,0,date("m")-1,date("d"),date("Y")));
			\MySQL::getDB()->doQuery("DELETE FROM mapsignatures WHERE updatedate < ? AND deleted > 0 AND sigtype != 'pos'", array($cleanupDate));

			return true;
		}

		function cleanupWormholes()
		{
			// Oude wormholes opruimen.
			$cleanupDate = date("Y-m-d H:i:s", mktime(date("H")-1,date("i"),date("s"),date("m"),date("d")-2,date("Y")));

			while ($results = \MySQL::getDB()->getRows("SELECT * FROM mapwormholes WHERE updatedate < ? AND permanent = 0", array($cleanupDate)))
			{
				foreach ($results as $result)
				{
					$wormhole = new \scanning\model\Wormhole();
					$wormhole->load($result);
					$wormhole->delete();
				}
			}

			// Oude connections opruimen
			while ($results = \MySQL::getDB()->getRows("SELECT * FROM mapwormholeconnections WHERE updatedate < ?", array($cleanupDate)))
			{
				foreach ($results as $result)
				{
					$connection = new \scanning\model\Connection();
					$connection->load($result);
					$connection->delete();
				}
			}

			return true;
		}
	}
}
?>