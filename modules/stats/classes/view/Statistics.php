<?php
namespace stats\view;

class Statistics
{
    function getOverview($arguments=[])
    {
        \AppRoot::title("Statistics");
        $console = new \stats\console\Stats();
        if (!\User::getUSER()->isAdmin())
            return \Tools::noRightMessage();

        $y = (count($arguments) > 0) ? array_shift($arguments) : date("Y");
        $m = (count($arguments) > 0) ? array_shift($arguments) : date("m");
        $sdate = date("Y-m-d", mktime(0, 0, 0, $m, 1, $y));
        $edate = date("Y-m-d", mktime(0, 0, 0, $m+1, 0, $y));

        $authGroups = \User::getUSER()->getAuthGroupsIDs();
        $authGroup = new \admin\model\AuthGroup($authGroups[0]);

        /** @var \stats\model\User[] $stats */
        $stats = array();
        $sortStatsBy = ($authGroup->getConfig("rank_leaderboard")=="wormholes")?"nrwormholes":"nrsigs";
        if ($results = \MySQL::getDB()->getRows("select *
                                                from    stats_users
                                                where   authgroupid = ?
                                                and     year = ? and month = ?
                                                order by score desc, ratio desc, ".$sortStatsBy." desc"
                                , [$authGroup->id, $y, $m]))
        {
            foreach ($results as $result)
            {
                $stat = new \stats\model\User();
                $stat->load($result);
                $stats[] = $stat;
            }
        }

        $corporations = array();
        foreach ($authGroup->getAllowedCorporations() as $corp) {
            $corporation = ["corp" => $corp, "stats" => array()];
            foreach ($stats as $stat) {
                if ($stat->corporationID == $corp->id)
                    $corporation["stats"][] = $stat;
            }
            $corporations[] = $corporation;
        }


        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("sdate", $sdate);
        $tpl->assign("edate", $edate);
        $tpl->assign("month", \Tools::getFullMonth(date("m",strtotime($sdate)))." ".date("Y",strtotime($sdate)));
        $tpl->assign("authGroup", $authGroup);
        $tpl->assign("corporations", $corporations);
        return $tpl->fetch("stats/overview");
    }
}