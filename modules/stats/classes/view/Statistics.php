<?php
namespace stats\view
{
	class Statistics
	{
		function getOverview($arguments=[])
		{
            \AppRoot::title("Statistics");
			$console = new \stats\console\Stats();
			if (!\User::getUSER()->isAdmin())
				return \Tools::noRightMessage();

            $date = date("Y-m-d");
            if (count($arguments) > 0)
                $date = date("Y-m-d", strtotime($arguments));
			if (\Tools::REQUEST("month"))
				$date = date("Y-m-d", mktime(0, 0, 0, date("m", strtotime($date)) + \Tools::REQUEST("month"), 1, date("Y", strtotime($date))));

			$sdate = date("Y-m-d", mktime(0, 0, 0, date("m", strtotime($date)), 1, date("Y", strtotime($date))));
			$edate = date("Y-m-d", mktime(0, 0, 0, date("m", strtotime($date)) + 1, 0, date("Y", strtotime($date))));

			$authGroups = \User::getUSER()->getAuthGroupsIDs();
			$authGroup = new \admin\model\AuthGroup($authGroups[0]);

			$corporations = array();
			foreach ($authGroup->getAllowedCorporations() as $corp)
			{
				$corporations[] = array("corp" => $corp,
										"users" => $console->getScannersByCorporationID($corp->id, $sdate, $edate));
			}

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("corporations", $corporations);
			$tpl->assign("sdate", $sdate);
			$tpl->assign("edate", $edate);
			$tpl->assign("month", \Tools::getFullMonth(date("m",strtotime($sdate)))." ".date("Y",strtotime($sdate)));
			return $tpl->fetch("stats/overview");
		}
	}
}