<?php
namespace map\console;

class Map
{
    /**
     * Oude signatures opruimen.
     * @return bool
     */
    function cleanupSignatures()
    {
        $cleanupDate = date("Y-m-d H:i:s", mktime(date("H")-1,date("i"),date("s"),date("m")-1,date("d"),date("Y")));
        \AppRoot::doCliOutput("Delete signatures older then ".$cleanupDate);
        if ($results = \MySQL::getDB()->getRows("select *
                                                 from   map_signature
                                                 where  deleted = 0 and updatedate < ?
                                                 and    sigtypeid not in (select id from map_signature_type where name in ('pos','citadel'))"
                                    , [$cleanupDate]))
        {
            \AppRoot::doCliOutput(" - ".count($results)." signatures to clean up");
            foreach ($results as $result) {
                $signature = new \map\model\Signature();
                $signature->load($result);
                if ($signature->getSignatureType()->mayCleanup())
                    $signature->delete();
            }
        }

        $cleanupDate = date("Y-m-d H:i:s", mktime(0,0,0,date("m")-2,date("d"),date("Y")));
        \AppRoot::doCliOutput("Purge really old signatures");
        \MySQL::getDB()->doQuery("delete from map_signature where updatedate < ? and deleted > 0", [$cleanupDate]);
        \MySQL::getDB()->doQuery("delete from map_anomaly where solarsystemid not in (select solarsystemid from mapwormholes where solarsystemid is not null and solarsystemid != 0)");
        //\MySQL::getDB()->doQuery("delete from map_signature where authgroupid in (select id from user_auth_groups where deleted > 0)");
        //\MySQL::getDB()->doQuery("delete from map_anomaly where authgroupid in (select id from user_auth_groups where deleted > 0)");
        return true;
    }

    /**
     * Oude wormholes opruimen.
     * @return bool
     */
    function cleanupWormholes()
    {
        $cleanupDate = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d")-2,date("Y")));
        \AppRoot::doCliOutput("Cleanup wormholes older then ".$cleanupDate);
        if ($results = \MySQL::getDB()->getRows("select * from mapwormholes where adddate < ?", [$cleanupDate])) {
            \AppRoot::doCliOutput(" - ".count($results)." wormholes to clean up");
            foreach ($results as $result) {
                $wormhole = new \map\model\Wormhole();
                $wormhole->load($result);
                if (!$wormhole->isPermenant())
                    $wormhole->delete();
            }
        }

        // Oude connections opruimen
        \AppRoot::doCliOutput("Cleanup connections with missing wormholes");
        \MySQL::getDB()->doQuery("delete from mapwormholeconnections where fromwormholeid not in (select id from mapwormholes) or towormholeid not in (select id from mapwormholes)");
        return true;
    }

    function cleanupCache()
    {
        \Tools::deleteDir("documents/cache");
        \Tools::deleteDir("documents/statistics");

        \MySQL::getDB()->doQuery("truncate mapnrofjumps");
        \MySQL::getDB()->doQuery("delete from map_character_locations where lastdate < ?", [date("Y-m-d", mktime(0,0,0,date("m")-1,date("d"),date("Y")))]);
        \MYSQL::getDB()->doQuery("delete from mapwormholejumplog where jumptime < ?", [date("Y-m-d", mktime(0,0,0,date("m")-6,date("d"),date("Y")))]);

        return true;
    }
}