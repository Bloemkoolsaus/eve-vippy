<?php
namespace stats\console;

class Stats
{
    function doCalc($arguments=[])
    {
        $date = date("Y-m-d");
        while (count($arguments) > 0) {
            $what = array_shift($arguments);
            if ($what == "yesterday")
                $date = date("Y-m-d", mktime(0,0,0,date("m"),date("d")-1,date("Y")));
            else
                $date = date("Y-m-d", strtotime($what));
        }
        $sdate = date("Y-m-d", mktime(0,0,0,date("m",strtotime($date)), 1, date("Y", strtotime($date))));
        $edate = date("Y-m-d", mktime(0,0,0,date("m",strtotime($date))+1, 0, date("Y", strtotime($date))));
        \AppRoot::doCliOutput("Calculate statistics ".$sdate." - ".$edate);


        /**
         * Online / Active users
         */
        if ($results = \MySQL::getDB()->getRows("select * from users where deleted = 0 and isvalid = 1"))
        {
            foreach ($results as $result)
            {
                $user = new \users\model\User();
                $user->load($result);

                $online = $user->getHoursOnline($sdate, $edate);
                \AppRoot::doCliOutput(" => ".$user->getFullName()." ".$online." hours online");
                if ($online == 0)
                    continue;

                foreach ($user->getAuthGroups() as $group)
                {
                    $stat = \stats\model\User::findOne([
                        "userid" => $user->id,
                        "authgroupid" => $group->id,
                        "year" => date("Y", strtotime($date)),
                        "month" => date("m", strtotime($date))
                    ]);
                    if (!$stat)
                        $stat = new \stats\model\User();

                    $stat->year = date("Y", strtotime($date));
                    $stat->month = date("m", strtotime($date));
                    $stat->userID = $user->id;
                    $stat->corporationID = $user->getMainCharacter()->corporationID;
                    $stat->authgroupID = $group->id;
                    $stat->hoursOnline = $online;
                    $stat->store();
                }
            }
        }


        /**
         * Signatures
         */
        \AppRoot::doCliOutput("Calc signatures");
        if ($results = \MySQL::getDB()->getRows("select s.userid, s.corpid, c.authgroupid, count(s.id) as amount
                                                from    stats_signatures s
                                                    inner join mapwormholechains c on c.id = s.chainid
                                                    inner join map_chain_settings cs on cs.chainid = c.id
                                                where   s.scandate between ? and ?
                                                and     cs.var = 'count-statistics'
                                                group by s.userid, c.authgroupid"
                                , [$sdate." 00:00:00", $edate." 23:59:59"]))
        {
            foreach ($results as $result)
            {
                $stat = \stats\model\User::findOne([
                    "userid" => $result["userid"],
                    "authgroupid" => $result["authgroupid"],
                    "year" => date("Y", strtotime($date)),
                    "month" => date("m", strtotime($date))
                ]);
                if (!$stat)
                    $stat = new \stats\model\User();

                $stat->year = date("Y", strtotime($date));
                $stat->month = date("m", strtotime($date));
                $stat->userID = $result["userid"];
                $stat->corporationID = $result["corpid"];
                $stat->authgroupID = $result["authgroupid"];
                $stat->nrSigs = $result["amount"];
                $stat->store();

                \AppRoot::doCliOutput(" => ".$stat->getUser()->getFullName()." ".$result["amount"]." signatures");
            }
        }

        /**
         * Mapped wormholes
         */
        \AppRoot::doCliOutput("Calc wormholes");
        if ($results = \MySQL::getDB()->getRows("select s.userid, s.corpid, c.authgroupid, count(distinct s.id) as amount
                                                from    stats_whmap s
                                                    inner join mapwormholechains c on c.authgroupid = s.authgroupid
                                                    inner join map_chain_settings cs on cs.chainid = c.id
                                                where   s.mapdate between ? and ?
                                                and     cs.var = 'count-statistics'
                                                group by s.userid, c.authgroupid"
                                , [$sdate." 00:00:00", $edate." 23:59:59"]))
        {
            foreach ($results as $result)
            {
                $stat = \stats\model\User::findOne([
                    "userid" => $result["userid"],
                    "authgroupid" => $result["authgroupid"],
                    "year" => date("Y", strtotime($date)),
                    "month" => date("m", strtotime($date))
                ]);
                if (!$stat)
                    $stat = new \stats\model\User();

                $stat->year = date("Y", strtotime($date));
                $stat->month = date("m", strtotime($date));
                $stat->userID = $result["userid"];
                $stat->corporationID = $result["corpid"];
                $stat->authgroupID = $result["authgroupid"];
                $stat->nrWormholes = $result["amount"];
                $stat->store();

                \AppRoot::doCliOutput(" => ".$stat->getUser()->getFullName()." ".$result["amount"]." wormholes mapped");
            }
        }

        /**
         * Kills
         */
        \AppRoot::doCliOutput("Calc kills");
        $kills = [];
        if ($results = \MySQL::getDB()->getRows("select *
                                                from    stats_kills
                                                where   killdate between ? and ?
                                                group by userid, shiptypeid"
                                , [$sdate." 00:00:00", $edate." 23:59:59"]))
        {
            foreach ($results as $result) {
                $kills[$result["userid"]][$result["shiptypeid"]] = $result["nrkills"];
            }
        }

        /** @var \eve\model\Ship[] $ships */
        $ships = [];
        foreach ($kills as $userID => $data)
        {
            /** @var \stats\model\User[] $stats */
            $stats = \stats\model\User::findAll([
                "userid" => $userID,
                "year" => date("Y", strtotime($date)),
                "month" => date("m", strtotime($date))
            ]);

            if (count($stats) == 0) {
                $user = new \users\model\User($userID);
                foreach ($user->getAuthGroups() as $group) {
                    $stat = new \stats\model\User();
                    $stat->userID = $user->id;
                    $stat->corporationID = $user->getMainCharacter()->corporationID;
                    $stat->authgroupID = $group->id;
                    $stat->year = date("Y", strtotime($date));
                    $stat->month = date("m", strtotime($date));
                    $stat->store();
                    $stats[] = $stat;
                }
            }

            foreach ($stats as $stat)
            {
                $stat->reqSigs = 0;
                $stat->nrKills = 0;
                \AppRoot::doCliOutput($stat->getUser()->getFullName());
                foreach ($data as $shipTypeID => $nrKills)
                {
                    $stat->nrKills += $nrKills;

                    if (!isset($ships[$shipTypeID]))
                        $ships[$shipTypeID] = new \eve\model\Ship($shipTypeID);

                    \AppRoot::doCliOutput("    => ".$nrKills." kills in ".$ships[$shipTypeID]->name." (".$ships[$shipTypeID]->getShipType().")");

                    if ($ships[$shipTypeID]->getShipRole() == "logi")
                        $stat->reqSigs += 0;
                    else if ($ships[$shipTypeID]->getShipRole() == "support")
                        $stat->reqSigs += 0;
                    else
                        $stat->reqSigs += $nrKills;
                }
                \AppRoot::doCliOutput("  => Total of ".$stat->nrKills." kills");
                if ($stat->reqSigs < 0)
                    $stat->reqSigs = $stat->nrKills;

                $stat->store();
            }
        }
    }
}