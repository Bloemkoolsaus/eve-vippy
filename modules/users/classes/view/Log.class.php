<?php
namespace users\view
{
	class Log
	{
		function showUserLog($userID)
		{
			$user = new \users\model\User($userID);

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("user", $user);
			return $tpl->fetch("users/log");
		}

		function getOverview()
		{
			$filter = (\Tools::POST("filter"))?:"exceptions";
			$sdate = date("Y-m-d", strtotime((\Tools::REQUEST("sdate"))?:"now"))." 00:00:00";
			$edate = date("Y-m-d", strtotime((\Tools::REQUEST("edate"))?:"now"))." 23:59:59";
			$search = (\Tools::POST("search"))?:"";


			$query = array();
			$query[] = "l.logdate BETWEEN '".$sdate."' AND '".$edate."'";

			if ($filter == "deletewhs")
				$query[] = "l.what = 'delete-wormhole'";
			if ($filter == "login")
				$query[] = "l.what = 'login'";

			if (strlen(trim($search)) > 0)
			{
				foreach (explode(" ",$search) as $keyword)
				{
					$keyword = \MySQL::escape($keyword);
					if (strlen(trim($keyword)) > 0)
						$query[] = "(u.displayname LIKE '%".$keyword."%' OR c.name LIKE '%".$keyword."%' OR p.name LIKE '%".$keyword."%')";
				}
			}

			if (\User::getUSER()->getMainCorporation()->allianceID != 0)
				$query[] = "corp.allianceid = ".\User::getUSER()->getMainCorporation()->allianceID;
			else
				$query[] = "corp.id = ".\User::getUSER()->getMainCorporation()->id;


			$logs = array();
			if ($results = \MySQL::getDB()->getRows("SELECT	l.*
													FROM	user_log l
														INNER JOIN users u ON u.id = l.userid
														INNER JOIN characters c ON c.userid = u.id
														INNER JOIN corporations corp ON corp.id = c.corpid
														LEFT JOIN characters p ON p.id = l.pilotid
													WHERE 	".implode(" AND ", $query)."
													GROUP BY l.id
													ORDER BY l.logdate DESC"
										, array()))
			{
				foreach ($results as $result)
				{
					$log = new \users\model\Log();
					$log->load($result);

					if ($filter == "exceptions")
					{
						if ($log->getLevel() != "critical" && $log->getLevel() != "notice")
							continue;
					}

					$logs[] = $log;
				}
			}

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("logs", $logs);
			$tpl->assign("filter", $filter);
			$tpl->assign("sdate", $sdate);
			$tpl->assign("edate", $edate);
			$tpl->assign("search", $search);
			return $tpl->fetch("users/log/overview");
		}
	}
}
?>