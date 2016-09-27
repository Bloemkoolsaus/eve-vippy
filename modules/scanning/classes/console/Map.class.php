<?php
namespace scanning\console
{
	class Map
	{
		function cleanupCache()
		{
			\Tools::deleteDir("documents/cache");
			\Tools::deleteDir("documents/statistics");

            \MySQL::getDB()->doQuery("TRUNCATE mapnrofjumps");
			\MySQL::getDB()->doQuery("delete from map_character_locations where lastdate < ?", [date("Y-m-d", mktime(0,0,0,date("m"),date("d")-1,date("Y")))]);
			\MYSQL::getDB()->doQuery("delete from mapwormholejumplog where jumptime < ?", [date("Y-m-d", mktime(0,0,0,date("m")-6,date("d"),date("Y")))]);

			return true;
		}

		function doMaintenance()
		{
			\AppRoot::setMaxExecTime(9999);
            \AppRoot::setMaxMemory("2G");
			$this->cleanupSignatures();
			$this->cleanupWormholes();
            return true;
		}

		function cleanupSignatures()
		{
			// Oude signatures opruimen.
			$cleanupDate = date("Y-m-d H:i:s", mktime(date("H")-1,date("i"),date("s"),date("m"),date("d")-3,date("Y")));
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM mapsignatures WHERE updatedate < ? AND deleted = 0 AND sigtype != 'pos'", array($cleanupDate)))
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
            $cleanupDate = date("Y-m-d H:i:s", mktime(date("H")-1,date("i"),date("s"),date("m"),date("d")-2,date("Y")));

			// Oude wormholes opruimen.
            if ($results = \MySQL::getDB()->getRows("SELECT * FROM mapwormholes WHERE adddate < ?", array($cleanupDate)))
			{
				foreach ($results as $result)
				{
					$wormhole = new \scanning\model\Wormhole();
					$wormhole->load($result);
                    if (!$wormhole->isPermenant())
					    $wormhole->delete();
				}
			}


			// Oude connections opruimen
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM mapwormholeconnections WHERE adddate < ?", array($cleanupDate)))
			{
				foreach ($results as $result)
				{
					$connection = new \scanning\model\Connection();
					$connection->load($result);

                    if ($connection->getFromWormhole()->isPermenant())
                        continue;
                    if ($connection->getToWormhole()->isPermenant())
                        continue;

                    $connection->delete();
				}
			}

			return true;
		}
	}
}
?>