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
                    $users[$mainCharacterID]["online"] = 0;
                    $users[$mainCharacterID]["signatures"] = 0;
                    $users[$mainCharacterID]["characters"] = [];

                    // Online time
                    if ($char->getUser()->getIsActive($sdate, $edate)) {
                        $activeUsers[$mainCharacterID] = $char->getUser()->getMainCharacter()->name;
                        $users[$mainCharacterID]["online"] = $char->getUser()->getHoursOnline($sdate, $edate);
                        $hoursOnline += $users[$mainCharacterID]["online"];
                    }

                    // Scanned signatures
                    $query = [
                        "corpid = ".$corporation->id,
                        "userid = ".$char->getUser()->id,
                        "scandate between '".$sdate." 00:00:00' and '".$edate." 23:59:59'"
                    ];
                    if ($result = \MySQL::getDB()->getRow("select count(*) as amount from stats_signatures where ".implode(" and ", $query))) {
                        $users[$mainCharacterID]["signatures"] = $result["amount"]-0;
                        $signatures += $result["amount"]-0;
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