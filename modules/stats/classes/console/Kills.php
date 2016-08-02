<?php
namespace stats\console;

class Kills
{
    function doImport($arguments=[])
    {
        $date = date("Y-m")."-01";
        if (count($arguments) > 0)
            $date = date("Y-m", strtotime(array_shift($arguments)))."-01";

        foreach (\admin\model\AuthGroup::getAuthGroups() as $authgroup)
        {
            if (!$authgroup->getConfig("stats_kills"))
                continue;

            \AppRoot::doCliOutput($authgroup->name);
            $api = new \api\Client();
            $api->baseURL = "http://stats.limited-power.co.uk/api/";

            foreach ($authgroup->getAlliances() as $alliance)
            {
                \AppRoot::doCliOutput(" => ".$alliance->name);
                $result = $api->get("rethink/year/".date("Y", strtotime($date))."/month/".date("m", strtotime($date))."/entity/".$alliance->id);
                if ($api->success())
                {
                    $data = json_decode($api->getResult());
                    foreach ($data as $dat)
                    {
                        if (!isset($dat->characterID))
                            continue;

                        $character = new \eve\model\Character($dat->characterID);
                        if ($character && $character->getUser() && $character->id == $character->getUser()->getMainCharacterID())
                        {
                            \AppRoot::doCliOutput("     ".$dat->reduction." kills for ".$character->getUser()->getFullName());
                            $totalKills = (isset($dat->reduction))?$dat->reduction:0;
                            $requiredSigs = 0;
                            $bonusPoints = 0;

                            if (isset($dat->shipsFlown))
                            {
                                foreach ($dat->shipsFlown as $typeID => $nrKills)
                                {
                                    $stats = \stats\model\Kills::findOne([
                                        "userid" => $character->getUser()->id,
                                        "shiptypeid" => $typeID,
                                        "killdate" => $date
                                    ]);
                                    if (!$stats) {
                                        $stats = new \stats\model\Kills();
                                        $stats->userID = $character->getUser()->id;
                                        $stats->shipTypeID = $typeID;
                                        $stats->killdate = $date;
                                    }

                                    $stats->nrKills = $nrKills;
                                    $stats->store();
                                }
                            }
                        }
                    }
                }
                else
                    \AppRoot::doCliOutput("Api call failed", "red");
            }
        }
    }
}