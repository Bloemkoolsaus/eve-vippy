<?php
namespace stats\view;

class Speed
{
    function getOverview($arguments=[])
    {
        $sdate = "2017-09-18 18:00:00";
        $edate = "2017-09-18 22:00:00";

        $solarSystems = [];
        if ($results = \MySQL::getDB()->getRows("select s.solarsystemid
                                                from    map_signature s
                                                    inner join users u on u.id = s.updateby
                                                    inner join characters c on c.userid = u.id
                                                    inner join corporations corp on corp.id = c.corpid
                                                where   corp.name = 'Viperfleet Inc.'
                                                and     s.scandate between ? and ?
                                                group by s.solarsystemid"
                                        , [$sdate, $edate]))
        {
            foreach ($results as $result) {
                $solarSystems[] = new \eve\model\SolarSystem($result["solarsystemid"]);
            }
        }

        $data = [];
        foreach ($solarSystems as $system)
        {
            if ($results = \MySQL::getDB()->getRows("select s.*
                                                    from    map_signature s
                                                        inner join users u on u.id = s.updateby
                                                        inner join characters c on c.userid = u.id
                                                        inner join corporations corp on corp.id = c.corpid
                                                    where   corp.name = 'Viperfleet Inc.'
                                                    and     s.scandate between ? and ?
                                                    and     s.solarsystemid = ?"
                                            , [$sdate, $edate, $system->id]))
            {
                foreach ($results as $result)
                {
                    $userID = $result["updateby"];

                    if (!isset($data[$userID]))
                        $data[$userID]["user"] = new \users\model\User($userID);

                    if (!isset($data[$userID]["system"][$system->id]))
                        $data[$userID]["system"][$system->id] = ["first" => null, "last" => null, "time" => 0, "total" => 0, "sigs" => []];

                    if (!$data[$userID]["system"][$system->id]["first"] || strtotime($data[$userID]["system"][$system->id]["first"]) > strtotime($result["updatedate"]))
                        $data[$userID]["system"][$system->id]["first"] = $result["updatedate"];
                    if (!$data[$userID]["system"][$system->id]["last"] || strtotime($data[$userID]["system"][$system->id]["last"]) < strtotime($result["updatedate"]))
                        $data[$userID]["system"][$system->id]["last"] = $result["updatedate"];

                    $data[$userID]["system"][$system->id]["sigs"][] = $result;
                    $data[$userID]["system"][$system->id]["total"]++;
                }
            }
        }

        echo "<pre>".print_r($data,true)."</pre>";
    }
}