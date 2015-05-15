<?php
namespace stats\view
{
	class Statistics
	{
		function getOverview()
		{
			if (!\User::getUSER()->getIsDirector())
				return \Tools::noRightMessage();


			$signatures = new \stats\controller\Signatures();

			$date = date("Y-m-d");
			if (\Tools::REQUEST("date"))
				$date = date("Y-m-d", strtotime(\Tools::REQUEST("date")));
			if (\Tools::REQUEST("month"))
				$date = date("Y-m-d", mktime(0,0,0, date("m", strtotime($date))+\Tools::REQUEST("month"), 1, date("Y", strtotime($date))));

			$topSDate = date("Y-m-d", mktime(0,0,0, date("m", strtotime($date)), 1, date("Y", strtotime($date))));
			$topEDate = date("Y-m-d", mktime(0,0,0, date("m", strtotime($date))+1, 0, date("Y", strtotime($date))));

			$corporations = array();
			$allianceID = \User::getUSER()->getMainCharacter()->getCorporation()->allianceID;
			foreach (\eve\model\Corporation::getCorporationsByAlliance($allianceID) as $corp)
			{
				$corporations[] = array("corp" => $corp,
										"users" => $signatures->getScannersByCorporationID($corp->id, $topSDate, $topEDate));
			}

			$scanners = $signatures->getTopScanners($topSDate, $topEDate);


			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("corporations", $corporations);
			$tpl->assign("scanners", $scanners);
			$tpl->assign("sdate", $topSDate);
			$tpl->assign("edate", $topEDate);
			$tpl->assign("month", \Tools::getFullMonth(date("m",strtotime($topSDate)))." ".date("Y",strtotime($topSDate)));
			return $tpl->fetch("stats/overview");
		}
	}
}
?>