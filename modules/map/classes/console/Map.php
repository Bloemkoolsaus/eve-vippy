<?php
namespace map\console;

class Map
{
    function doMaintenance()
    {
        \AppRoot::setMaxExecTime(9999);
        \AppRoot::setMaxMemory("2G");
        $this->cleanupSignatures();
        $this->cleanupWormholes();
        return true;
    }

    /**
     * Oude signatures opruimen.
     * @return bool
     */
    function cleanupSignatures()
    {
        $cleanupDate = date("Y-m-d H:i:s", mktime(date("H")-1,date("i"),date("s"),date("m"),date("d")-3,date("Y")));
        if ($results = \MySQL::getDB()->getRows("SELECT * FROM map_signature WHERE updatedate < ? AND deleted = 0 AND sigtype != 'pos'", array($cleanupDate)))
        {
            foreach ($results as $result)
            {
                $signature = new \map\model\Signature();
                $signature->load($result);
                if ($signature->getSignatureType()->mayCleanup())
                    $signature->delete();
            }
        }

        $cleanupDate = date("Y-m-d H:i:s", mktime(0,0,0,date("m")-1,date("d"),date("Y")));
        \MySQL::getDB()->doQuery("DELETE FROM map_signature WHERE updatedate < ? AND deleted > 0 AND sigtype != 'pos'", array($cleanupDate));
        return true;
    }

    /**
     * Oude wormholes opruimen.
     * @return bool
     */
    function cleanupWormholes()
    {
        $cleanupDate = date("Y-m-d H:i:s", mktime(date("H")-1,date("i"),date("s"),date("m"),date("d")-2,date("Y")));
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

    function cleanupCache()
    {
        \Tools::deleteDir("documents/cache");
        \Tools::deleteDir("documents/statistics");

        \MySQL::getDB()->doQuery("truncate mapnrofjumps");
        \MySQL::getDB()->doQuery("delete from map_character_locations where lastdate < ?", [date("Y-m-d", mktime(0,0,0,date("m"),date("d")-1,date("Y")))]);
        \MYSQL::getDB()->doQuery("delete from mapwormholejumplog where jumptime < ?", [date("Y-m-d", mktime(0,0,0,date("m")-6,date("d"),date("Y")))]);

        return true;
    }
}