<?php
\AppRoot::doCliOutput("Fix whmap statistics for November!");
$maps = [];
$totalStats = 0;
if ($results = \MySQL::getDB()->getRows("select * from stats_whmap where mapdate > '2016-11-01 00:00:00'"))
{
    foreach ($results as $result)
    {
        $stat = new \stats\model\Whmap();
        $stat->load($result);

        $maps[$stat->authGroupID][$stat->pilotID][$stat->systemID][] = $stat;
        $totalStats++;
    }
}

\AppRoot::doCliOutput($totalStats." whmap stats found");
foreach ($maps as $authGroupID => $pilots)
{
    $authGroup = new \admin\model\AuthGroup($authGroupID);
    \AppRoot::doCliOutput(" > ".$authGroup->name.": ".count($pilots)." pilots");
    foreach ($pilots as $pilotID => $systems)
    {
        $pilot = new \eve\model\Character($pilotID);
        \AppRoot::doCliOutput("    * ".$pilot->name.": ".count($systems)." systems");
        foreach ($systems as $systemID => $stats)
        {
            $system = new \eve\model\SolarSystem($systemID);
            \AppRoot::doCliOutput("      ".$system->name.": ".count($stats)." records");
            while (count($stats) > 1) {
                $stat = array_shift($stats);
                \AppRoot::doCliOutput("         Removing stat ".$stat->id);
                $stat->delete();
            }
        }
    }
}