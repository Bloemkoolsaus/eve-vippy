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
        $characters = \eve\model\Character::findAll(["corpid" => $corporation->id]);
        foreach ($characters as $char) {
            if ($char->userID && !isset($users[$char->userID])) {
                $user = new \users\model\User($char->userID);
                $users[$user->id] = $user;
                if ($user->getIsActive($sdate, $edate)) {
                    $activeUsers[$user->id] = $user;
                    $hoursOnline += $user->getHoursOnline($sdate, $edate);
                }
            }
        }

        /* Signatures tellen */
        $signatures = 0;
        $query = [
            "corpid = ".$corporation->id,
            "scandate between '".$sdate." 00:00:00' and '".$edate." 23:59:59'"
        ];
        if ($result = \MySQL::getDB()->getRow("select count(*) as amount from stats_signatures where ".implode(" and ", $query))) {
            $signatures = $result["amount"];
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
            "users" => [
                "characters" => count($characters),
                "registered" => count($users),
                "active" => count($activeUsers)
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