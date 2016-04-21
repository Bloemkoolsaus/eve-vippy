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

        $y = (count($arguments) > 0) ? array_shift($arguments) : date("Y");
        $m = (count($arguments) > 0) ? array_shift($arguments) : date("m");

        $sdate = date("Y-m-d", mktime(0, 0, 0, $m, 1, $y));
        $edate = date("Y-m-d", mktime(0, 0, 0, $m+1, 0, $y));

        $allSignatures = $console->getTopScanners($sdate, $edate);
        foreach ($allSignatures as $key => $sig)
        {
            if (count($topSignatures) < 10)
                $topSignatures[] = $sig;
            if ($sig["user"]->id == \User::getUSER()->id)
                $userSignature = $sig;
        }

        $totalSDate = date("Y-m-d", mktime(0,0,0, date("m")-10, 1, date("Y")));
        $totalEDate = date("Y-m-d", mktime(0,0,0, date("m")+1, 0, date("Y")));
        $totalSignatures = $console->getTotalSignatures($totalSDate, $totalEDate, null, null, 10);

        $doRaffles = false;
        foreach (\User::getUSER()->getAuthGroups() as $group)
        {
            if ($group->hasModule("stats"))
                $doRaffles = true;
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("topsignatures", $topSignatures);
        $tpl->assign("allsignatures", $allSignatures);
        $tpl->assign("usersignature", $userSignature);
        $tpl->assign("totalsignatures", $totalSignatures);
        $tpl->assign("doraffles", $doRaffles);
        $tpl->assign("sdate", $sdate);
        $tpl->assign("edate", $edate);
        $tpl->assign("month", \Tools::getFullMonth(date("m",strtotime($sdate)))." ".date("Y",strtotime($sdate)));
        return $tpl->fetch("stats/leaderboard");
    }
}