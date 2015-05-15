<?php
namespace stats\view
{
	class Leaderboard
	{
		function getOverview()
		{
			$signatures = new \stats\controller\Signatures();
			$topSignatures = array();
			$userSignature = array(	"user" 	=> \User::getUSER(),
									"rank"	=> 0,
									"amount"=> 0);

			$date = date("Y-m-d");
			if (\Tools::REQUEST("date"))
				$date = date("Y-m-d", strtotime(\Tools::REQUEST("date")));
			if (\Tools::REQUEST("month"))
				$date = date("Y-m-d", mktime(0,0,0, date("m", strtotime($date))+\Tools::REQUEST("month"), 1, date("Y", strtotime($date))));

			$topSDate = date("Y-m-d", mktime(0,0,0, date("m", strtotime($date)), 1, date("Y", strtotime($date))));
			$topEDate = date("Y-m-d", mktime(0,0,0, date("m", strtotime($date))+1, 0, date("Y", strtotime($date))));
			$allSignatures = $signatures->getTopScanners($topSDate, $topEDate);
			foreach ($allSignatures as $key => $sig)
			{
				if (count($topSignatures) < 10)
					$topSignatures[] = $sig;
				if ($sig["user"]->id == \User::getUSER()->id)
					$userSignature = $sig;
			}

			$totalSDate = date("Y-m-d", mktime(0,0,0, date("m")-10, 1, date("Y")));
			$totalEDate = date("Y-m-d", mktime(0,0,0, date("m")+1, 0, date("Y")));
			$totalSignatures = $signatures->getTotalSignatures($totalSDate, $totalEDate, null, null, 10);

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
			$tpl->assign("sdate", $topSDate);
			$tpl->assign("edate", $topEDate);
			$tpl->assign("month", \Tools::getFullMonth(date("m",strtotime($topSDate)))." ".date("Y",strtotime($topSDate)));
			return $tpl->fetch("stats/leaderboard");
		}
	}
}
?>