<?php
namespace stats\controller
{
	class Signatures
	{
		function getTopScanners($fromdate, $tilldate, $authGroupID=null, $chainID=null, $limitRows=null)
		{
			$fromdate = date("Y-m-d", strtotime($fromdate))." 00:00:00";
			$tilldate = date("Y-m-d", strtotime($tilldate))." 23:59:59";

			if ($authGroupID == null || !is_numeric($authGroupID)) {
				$authGroups = \User::getUSER()->getAuthGroupsIDs();
				$authGroupID = $authGroups[0];
			}
			$authGroup = new \admin\model\AuthGroup($authGroupID);


			$queries = array();
			$queries[] = "s.scandate BETWEEN '".$fromdate."' AND '".$tilldate."'";
			$queries[] = "c.authgroupid = ".$authGroup->id;

			if ($chainID !== null && is_numeric($chainID))
				$queries[] = "c.id = ".$chainID;
			else
				$queries[] = "c.countinstats > 0";

			$limit = "";
			if ($limitRows !== null && is_numeric($limitRows))
				$limit = "LIMIT ".$limitRows;

			$scanners = array();
			if ($results = \MySQL::getDB()->getRows("SELECT	u.*, COUNT(s.id) AS amount
													FROM	users u
														INNER JOIN stats_signatures s ON s.userid = u.id
														INNER JOIN mapwormholechains c ON c.id = s.chainid
													WHERE	".implode(" AND ", $queries)."
													GROUP BY u.id
													ORDER BY COUNT(s.id) DESC
													".$limit))
			{
				foreach ($results as $result)
				{
					$user = new \users\model\User();
					$user->load($result);

					$scanners[] = array("user"	=> $user,
										"rank"	=> count($scanners)+1,
										"amount"=> $result["amount"]);
				}
			}

			return $scanners;
		}

		function getTotalSignatures($fromdate, $tilldate, $authGroupID=null, $chainID=null, $limit=10)
		{
			$fromdate = date("Y-m-d", strtotime($fromdate))." 00:00:00";
			$tilldate = date("Y-m-d", strtotime($tilldate))." 23:59:59";
			if ($authGroupID == null || !is_numeric($authGroupID))
			{
				$authGroups = \User::getUSER()->getAuthGroupsIDs();
				$authGroupID = $authGroups[0];
			}
			$authGroup = new \admin\model\AuthGroup($authGroupID);


			$queries = array();
			$queries[] = "s.scandate BETWEEN '".$fromdate."' AND '".$tilldate."'";
			$queries[] = "c.authgroupid = ".$authGroup->id;

			if ($chainID !== null && is_numeric($chainID))
				$queries[] = "c.id = ".$chainID;
			else
				$queries[] = "c.countinstats > 0";

			$totals = array();
			if ($results = \MySQL::getDB()->getRows("SELECT	MONTH(s.scandate) AS `month`,  year(s.scandate) AS `year`,
															COUNT(s.id) AS amount
													FROM 	stats_signatures s
														INNER JOIN mapwormholechains c ON c.id = s.chainid
													WHERE	".implode(" AND ", $queries)."
													GROUP BY month(s.scandate), year(s.scandate)
													ORDER BY year(s.scandate) DESC, month(s.scandate) DESC"))
			{
				foreach ($results as $result)
				{
					$totals[] = array(	"date"	=> \Tools::getFullMonth($result["month"])." ".$result["year"],
										"amount"=> $result["amount"]);
				}
			}

			return $totals;
		}

		function getScannersByCorporationID($corporationID, $fromdate, $tilldate)
		{
			$users = array();
			$scannerIDs = array();
			foreach ($this->getTopScanners($fromdate, $tilldate, null, null, null) as $user)
			{
				if ($user["user"]->getMainCharacter()->corporationID == $corporationID) {
					$scannerIDs[] = $user["user"]->id;
					$users[] = $user;
				}
			}
			foreach (\users\model\User::getUsersByCorporation($corporationID) as $user)
			{
				if ($user->getMainCharacter()->corporationID == $corporationID) {
					if (!in_array($user->id, $scannerIDs)) {
						if ($user->getIsActive($fromdate, $tilldate))
							$users[] = array("user" => $user, "rank" => 0, "amount" => 0);
					}
				}
			}

			return $users;
		}
	}
}
?>