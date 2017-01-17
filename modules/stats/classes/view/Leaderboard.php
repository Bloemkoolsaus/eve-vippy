<?php
namespace stats\view;

class Leaderboard
{
    function getOverview($arguments=[])
    {
        \AppRoot::title("Leaderboard");
        $console = new \stats\console\Stats();
        $topSignatures = [];
        $userSignature = [
            "user" 	=> \User::getUSER(),
            "rank"	=> 0,
            "amount"=> 0
        ];
        $authGroups = \User::getUSER()->getAuthGroupsIDs();
        $authGroup = new \admin\model\AuthGroup($authGroups[0]);

        $y = (count($arguments) > 0) ? array_shift($arguments) : date("Y");
        $m = (count($arguments) > 0) ? array_shift($arguments) : date("m");

        $sort = (count($arguments) > 0) ? array_shift($arguments) : null;
        if (!$sort) {
            if ($authGroup->getConfig("stats_kills"))
                $sort = "score";
            elseif ($authGroup->getConfig("rank_leaderboard")=="wormholes")
                $sort = "whs";
            else
                $sort = "sigs";
        }

        $sdate = date("Y-m-d", mktime(0, 0, 0, $m, 1, $y));
        $edate = date("Y-m-d", mktime(0, 0, 0, $m+1, 0, $y));
        \AppRoot::doCliOutput("sdate: ".$sdate." || edate: ".$edate);

        /** @var \stats\model\User[] $stats */
        $stats = array();
        $user = null;

        if ($sort == "score")
            $sortStatsBy = "score desc, ratio desc, nrsigs desc, hoursonline desc";
        else if ($sort == "whs")
            $sortStatsBy = "nrwormholes desc, nrsigs desc, hoursonline desc";
        else
            $sortStatsBy = "nrsigs desc, nrwormholes desc, hoursonline desc";

        if ($results = \MySQL::getDB()->getRows("select *
                                                from    stats_users
                                                where   authgroupid = ?
                                                and     year = ? and month = ?
                                                order by ".$sortStatsBy
                                , [$authGroup->id, date("Y", strtotime($sdate)), date("m", strtotime($sdate))]))
        {
            foreach ($results as $result)
            {
                $stat = new \stats\model\User();
                $stat->load($result);
                $stats[] = $stat;
            }
        }

        $totalSignatures = array();
        if ($results = \MySQL::getDB()->getRows("select year, month, sum(nrsigs) as sigs, sum(nrwormholes) as wormholes
                                                from    stats_users
                                                where   authgroupid = ?
                                                group by year, month
                                                order by year desc, month desc"
            , [$authGroup->id]))
        {
            foreach ($results as $result)
            {
                $totalSignatures[] = [
                    "date" => \Tools::getFullMonth($result["month"])." ".$result["year"],
                    "month" => $result["month"],
                    "year" => $result["year"],
                    "sigs" => $result["sigs"],
                    "whs" => $result["wormholes"],
                    "selected" => ($result["year"].$result["month"] == date("Ym", strtotime($sdate)))?true:false
                ];
            }
        }


        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("stats", $stats);
        $tpl->assign("sdate", $sdate);
        $tpl->assign("edate", $edate);
        $tpl->assign("sort", $sort);
        $tpl->assign("month", \Tools::getFullMonth(date("m",strtotime($sdate)))." ".date("Y",strtotime($sdate)));
        $tpl->assign("authGroup", $authGroup);
        $tpl->assign("totalSignatures", $totalSignatures);
        $tpl->assign("personalStats", \stats\model\User::findOne(["userid" => \User::getUSER()->id, "year" => $y, "month" => $m]));
        return $tpl->fetch("stats/leaderboard");
    }
}