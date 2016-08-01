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
                            $stats = \stats\model\Kills::findOne(["userid" => $character->getUser()->id, "killdate" => $date]);
                            if (!$stats)
                                $stats = new \stats\model\Kills();

                            $totalKills = (isset($dat->reduction))?$dat->reduction:0;
                            $requiredSigs = 0;
                            $bonusPoints = 0;

                            if (isset($dat->shipsFlown)) {
                                foreach ($dat->shipsFlown as $typeID => $nrKills) {
                                    $ship = new \eve\model\Ship($typeID);

                                    // Logi?
                                    $isLogistics = false;
                                    if (strtolower($ship->getShipType()) == "logistics")
                                        $isLogistics = true;
                                    if (strtolower($ship->getShipType()) == "logistics frigate")
                                        $isLogistics = true;
                                    if (strtolower($ship->name) == "nestor")
                                        $isLogistics = true;

                                    // Support
                                    $isSupport = false;
                                    if ($isLogistics)
                                        $isSupport = true;
                                    if (strtolower($ship->getShipType()) == "force recon ship")
                                        $isSupport = true;
                                    if (strtolower($ship->getShipType()) == "combat recon ship")
                                        $isSupport = true;
                                    if (strtolower($ship->getShipType()) == "heavy interdiction cruiser")
                                        $isSupport = true;
                                    if (strtolower($ship->getShipType()) == "interdictor")
                                        $isSupport = true;
                                    if (strtolower($ship->isCapital()))
                                        $isSupport = true;


                                    // Support niet meetellen
                                    if ($isSupport)
                                        $bonusPoints += ($nrKills * 5);

                                    // Logi een extra punt
                                    if ($isLogistics)
                                        $bonusPoints += 1;
                                }
                            }

                            $requiredSigs = ($totalKills*5)-$bonusPoints;
                            if ($requiredSigs < 0)
                                $requiredSigs = 0;

                            $stats->userID = $character->getUser()->id;
                            $stats->nrKills = $totalKills;
                            $stats->requiredSigs = $requiredSigs;
                            $stats->killdate = $date;
                            $stats->store();
                        }
                    }
                }
                else
                    \AppRoot::doCliOutput("Api call failed", "red");
            }
        }
    }
}