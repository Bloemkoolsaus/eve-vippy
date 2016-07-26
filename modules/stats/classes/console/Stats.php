<?php
namespace stats\console;

class Stats
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

        $queries = ["c.authgroupid = ".$authGroup->id];

        if ($chainID !== null && is_numeric($chainID))
            $queries[] = "c.id = ".$chainID;
        else
            $queries[] = "c.id IN (SELECT chainid FROM map_chain_settings WHERE var = 'count-statistics')";

        $orderBy = ($authGroup->getConfig("rank_leaderboard")=="wormholes") ? "whs.amount desc, sigs.amount desc" : "sigs.amount desc, whs.amount desc";

        $limit = "";
        if ($limitRows !== null && is_numeric($limitRows))
            $limit = "LIMIT ".$limitRows;

        $scanners = array();
        if ($results = \MySQL::getDB()->getRows("select u.*, sigs.amount as sigs, whs.amount as whs, kills.amount as kills
                                                from    users u
                                                    left join ( select  s.userid, count(s.id) as amount
                                                                from    stats_signatures s
                                                                    inner join mapwormholechains c on c.id = s.chainid
                                                                where   s.scandate between '".$fromdate."' and '".$tilldate."'
                                                                and     ".implode(" and ", $queries)."
                                                                group by s.userid
                                                        ) as sigs on sigs.userid = u.id
                                                    left join ( select  s.userid, count(s.id) as amount
                                                                from    stats_whmap s
                                                                    inner join mapwormholechains c on c.id = s.chainid
                                                                where   s.mapdate between '".$fromdate."' and '".$tilldate."'
                                                                and     ".implode(" and ", $queries)."
                                                                group by s.userid
                                                                order by count(s.id) desc
                                                        ) whs on whs.userid = u.id
                                                    left join ( select  userid, nrkills as amount
                                                                from    stats_kills
                                                        ) kills on kills.userid = u.id

                                                where   sigs.userid is not null
                                                    or  whs.userid is not null
                                                order by ".$orderBy." ".$limit))
        {
            foreach ($results as $result)
            {
                $user = new \users\model\User();
                $user->load($result);

                $scanners[] = [
                    "user"	=> $user,
                    "rank"	=> count($scanners)+1,
                    "sigs"  => $result["sigs"],
                    "whs"   => $result["whs"],
                    "kills" => $result["kills"]
                ];
            }
        }

        return $scanners;
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
            if ($user->getIsActive($fromdate, $tilldate))
            {
                if ($user->getMainCharacter()->corporationID == $corporationID && !in_array($user->id, $scannerIDs))
                {
                    $users[] = [
                        "user"	=> $user,
                        "rank"	=> 0,
                        "sigs"  => 0,
                        "whs"   => 0
                    ];
                }
            }
        }

        return $users;
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
        $queries[] = "c.authgroupid = ".$authGroup->id;

        if ($chainID !== null && is_numeric($chainID))
            $queries[] = "c.id = ".$chainID;
        else
            $queries[] = "c.id IN (SELECT chainid FROM map_chain_settings WHERE var = 'count-statistics')";

        $totals = array();
        if ($results = \MySQL::getDB()->getRows("SELECT	MONTH(s.scandate) AS `month`,  year(s.scandate) AS `year`,
                                                        COUNT(s.id) AS amount
                                                FROM 	stats_signatures s
                                                    INNER JOIN mapwormholechains c ON c.id = s.chainid
                                                WHERE	s.scandate BETWEEN '".$fromdate."' AND '".$tilldate."'
                                                AND     ".implode(" AND ", $queries)."
                                                GROUP BY month(s.scandate), year(s.scandate)
                                                ORDER BY year(s.scandate) DESC, month(s.scandate) DESC"))
        {
            foreach ($results as $result)
            {
                $year = $result["year"];
                $month = $result["month"];
                while (strlen($month) < 2) {
                    $month = "0" . $month;
                }

                if (!isset($totals[$year.$month])) {
                    $totals[$year.$month] = [
                        "date" => \Tools::getFullMonth($result["month"])." ".$result["year"],
                        "sigs" => 0, "whs" => 0
                    ];
                }

                $totals[$year.$month]["sigs"] = $result["amount"];
            }
        }

        if ($results = \MySQL::getDB()->getRows("SELECT	MONTH(s.mapdate) AS `month`,  year(s.mapdate) AS `year`,
                                                        COUNT(s.id) AS amount
                                                FROM 	stats_whmap s
                                                    INNER JOIN mapwormholechains c ON c.id = s.chainid
                                                WHERE	s.mapdate BETWEEN '".$fromdate."' AND '".$tilldate."'
                                                AND     ".implode(" AND ", $queries)."
                                                GROUP BY month(s.mapdate), year(s.mapdate)
                                                ORDER BY year(s.mapdate) DESC, month(s.mapdate) DESC"))
        {
            foreach ($results as $result)
            {
                $year = $result["year"];
                $month = $result["month"];
                while (strlen($month) < 2) {
                    $month = "0" . $month;
                }

                if (!isset($totals[$year.$month])) {
                    $totals[$year.$month] = [
                        "date" => \Tools::getFullMonth($result["month"])." ".$result["year"],
                        "sigs" => 0, "whs" => 0
                    ];
                }

                $totals[$year.$month]["whs"] = $result["amount"];
            }
        }

        krsort($totals);
        return $totals;
    }
}