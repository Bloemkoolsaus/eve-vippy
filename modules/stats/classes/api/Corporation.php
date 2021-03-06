<?php
namespace stats\api;

class Corporation extends \api\Server
{
    function getDefault($arguments=[])
    {
        $corporation = new \eve\model\Corporation(array_shift($arguments));
        $year = (count($arguments) > 0) ? array_shift($arguments) : date("Y");
        $month = (count($arguments) > 0) ? array_shift($arguments) : date("m");

        $sdate = date("Y-m-d", mktime(0,0,0, $month, 1, $year));
        $edate = date("Y-m-d", mktime(0,0,0, $month+1, 0, $year));

        /* Users tellen */
        $users = [];
        $activeUsers = [];
        $hoursOnline = 0;
        $signatures = 0;
        $characters = \eve\model\Character::findAll(["corpid" => $corporation->id]);
        foreach ($characters as $char)
        {
            if ($char->getUser())
            {
                $mainCharacterID = (int)$char->getUser()->getMainCharacter()->id;

                if (!isset($users[$mainCharacterID]))
                {
                    $users[$mainCharacterID]["user"] = $char->getUser()->getMainCharacter()->name;
                    $users[$mainCharacterID]["online"] = ["total" => 0, "hours" => [], "days" => []];
                    $users[$mainCharacterID]["signatures"] = ["total" => 0, "hours" => []];
                    $users[$mainCharacterID]["characters"] = [];

                    // Online time
                    if ($char->getUser()->getIsActive($sdate, $edate)) {
                        $activeUsers[$mainCharacterID] = $char->getUser()->getMainCharacter()->name;
                        $users[$mainCharacterID]["online"]["total"] = $char->getUser()->getHoursOnline($sdate, $edate);
                        $hoursOnline += $users[$mainCharacterID]["online"]["total"];

                        foreach (\users\model\Log::getLogByUserOnDate($char->getUser()->id, $sdate, $edate, "ingame") as $log) {
                            if ($log->pilotID) {
                                if (!isset($users[$mainCharacterID]["online"]["days"][date("Y-m-d", strtotime($log->logDate))]))
                                    $users[$mainCharacterID]["online"]["days"][date("Y-m-d", strtotime($log->logDate))]["total"] = 0;
                                $seconds = (strtotime($log->lastDate)-strtotime($log->logDate));
                                if ($users[$mainCharacterID]["online"]["days"][date("Y-m-d", strtotime($log->logDate))]["total"] < $seconds) {
                                    $users[$mainCharacterID]["online"]["days"][date("Y-m-d", strtotime($log->logDate))]["total"] = round(($seconds/60)/60,2);

                                    $increment = 30; // seconden
                                    $curtime = strtotime($log->lastDate);
                                    while ($curtime > strtotime($log->logDate)) {
                                        if (!isset($users[$mainCharacterID]["online"]["days"][date("Y-m-d", $curtime)]["hours"][date("H", $curtime)]))
                                            $users[$mainCharacterID]["online"]["days"][date("Y-m-d", $curtime)]["hours"][date("H", $curtime)] = 0;
                                        $users[$mainCharacterID]["online"]["days"][date("Y-m-d", $curtime)]["hours"][date("H", $curtime)] += $increment;
                                        $curtime -= $increment;
                                    }
                                }
                            }
                        }
                    }

                    // Scanned signatures
                    $query = [
                        "userid = ".$char->getUser()->id,
                        "scandate between '".$sdate." 00:00:00' and '".$edate." 23:59:59'"
                    ];
                    if ($results = \MySQL::getDB()->getRows("select hour(scandate) as hour, count(*) as amount 
                                                             from   stats_signatures 
                                                             where ".implode(" and ", $query)."
                                                             group by hour(scandate)"))
                    {
                        foreach ($results as $result) {
                            $users[$mainCharacterID]["signatures"]["total"] += $result["amount"]-0;
                            $users[$mainCharacterID]["signatures"]["hours"][$result["hour"]] = $result["amount"]-0;
                            $signatures += $result["amount"]-0;
                        }
                    }
                }
                $users[$mainCharacterID]["characters"][$char->id] = $char->name;
            }
        }


        /* Wormholes tellen */
        $wormholes = 0;
        $query = [
            "corpid = ".$corporation->id,
            "mapdate between '".$sdate." 00:00:00' and '".$edate." 23:59:59'"
        ];
        if ($result = \MySQL::getDB()->getRow("select count(*) as amount from stats_whmap where ".implode(" and ", $query))) {
            $wormholes = $result["amount"];
        }

        $data = [
            "id" => $corporation->id,
            "ticker" => $corporation->ticker,
            "name" => $corporation->name,
            "year" => $year,
            "month" => $month,
            "users" => [
                "characters" => count($characters),
                "registered" => count($users),
                "active" => count($activeUsers),
                "users" => $users
            ],
            "stats" => [
                "hoursonline" => $hoursOnline,
                "signatures" => $signatures,
                "wormholes" => $wormholes
            ]
        ];

        return $data;
    }
}