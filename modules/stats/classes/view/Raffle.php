<?php
namespace stats\view
{
	class Raffle
	{
		function getOverview()
		{
			$date = date("Y-m-d", mktime(0,0,0,date("m")-1,date("d"),date("Y")));
			if (\Tools::REQUEST("date"))
				$date = date("Y-m-d", strtotime(\Tools::REQUEST("date")));
			if (\Tools::REQUEST("month"))
				$date = date("Y-m-d", mktime(0,0,0, date("m", strtotime($date))+\Tools::REQUEST("month"), date("d"), date("Y", strtotime($date))));

			$future = false;
			if (strtotime(date("Y-m", strtotime($date))."-01") >= strtotime(date("Y-m")."-01"))
				$future = true;


			$topSDate = date("Y-m-d", mktime(0,0,0, date("m", strtotime($date)), 1, date("Y", strtotime($date))))." 00:00:00";
			$topEDate = date("Y-m-d", mktime(0,0,0, date("m", strtotime($date))+1, 0, date("Y", strtotime($date))))." 23:59:59";


			$authGroups = \User::getUSER()->getAuthGroups();
			$authGroup = $authGroups[0];


			$query = array();
			$query[] = "c.authgroupid = ".$authGroup->id;

			if ($authGroup->mainChainID !== null)
				$query[] = "c.id = ".$authGroup->mainChainID;


			$scanners = array();
			if ($results = \MySQL::getDB()->getRows("SELECT	u.*, COUNT(s.id) AS amount
													FROM 	stats_signatures s
														INNER JOIN users u ON u.id = s.userid
														INNER JOIN mapwormholechains c ON c.id = s.chainid
													WHERE	s.scandate BETWEEN ? AND ?
													AND		".implode(" AND ", $query)."
													GROUP BY u.id
													ORDER BY COUNT(s.id) DESC"
										, array($topSDate, $topEDate, $authGroup->id)))
			{
				foreach ($results as $result)
				{
					$user = new \users\model\User();
					$user->load($result);
					$scanners[] = array("user"		=> $user,
										"amount"	=> $result["amount"]);
				}
			}

			if (!$future)
			{
				$cacheFile = "statistics/scanning/raffle/tickets.".date("Y-m",strtotime($date));
				if (!$tickets = \Cache::file()->get($cacheFile))
				{
					$tickets = array();
					if ($results = \MySQL::getDB()->getRows("SELECT	u.*
															FROM 	stats_signatures s
															    INNER JOIN users u ON u.id = s.userid
																INNER JOIN mapwormholechains c ON c.id = s.chainid
															WHERE	s.scandate BETWEEN ? AND ?
															AND		".implode(" AND ", $query)."
															ORDER BY s.id"
												, array($topSDate, $topEDate, $authGroup->id)))
					{
						foreach ($results as $result)
						{
							$user = new \users\model\User();
							$user->load($result);
							$tickets[] = $user->getFullName();
						}
					}
					$tickets = json_encode($tickets);
                    \Cache::file()->set($cacheFile, $tickets);
				}
			}

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("future", $future);
			$tpl->assign("sdate", $topSDate);
			$tpl->assign("edate", $topEDate);
			$tpl->assign("scanners", $scanners);
			$tpl->assign("month", \Tools::getFullMonth(date("m",strtotime($topSDate)))." ".date("Y",strtotime($topSDate)));
			return $tpl->fetch("stats/raffle");
		}

		function rollTicket()
		{
			$cacheFile = "statistics/scanning/raffle/tickets.".date("Y-m",strtotime(\Tools::REQUEST("date")));
			if (!$tickets = \Cache::file()->get($cacheFile))
				return "<span style='color:red;'>unkown</span>";

			$tickets = json_decode($tickets,true);
			if (isset($tickets[\Tools::REQUEST("ticket")]))
				return $tickets[\Tools::REQUEST("ticket")];
			else
				return "<span style='color:red;'>unkown</span>";
		}
	}
}
?>