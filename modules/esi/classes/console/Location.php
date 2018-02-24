<?php
namespace esi\console;

class Location
{
    function doDefault($arguments=[])
    {
        \AppRoot::setMaxExecTime(60);
        \AppRoot::setMaxMemory("2G");
        \AppRoot::doCliOutput("doLocations(".implode(",",$arguments).")");

        $crestLimit = (int)((\Config::getCONFIG()->get("crest_location_limit"))?:15);
        $crestTimer = (int)((\Config::getCONFIG()->get("crest_location_timer"))?:5);

        // Als we tegen de timeout aanlopen, afbreken
        while (\AppRoot::getExecTime() < 58)
        {
            \AppRoot::doCliOutput("Find characters");

            // Online toons (die niet in fleet zitten!)
            if ($results = \MySQL::getDB()->getRows("select c.id, c.name, l.solarsystemid, l.shiptypeid, l.lastdate as lastupdate, l.online
                                                    from    characters c
                                                        inner join users u on u.id = c.userid
                                                        inner join sso_token t on t.tokenid = c.id and t.tokentype = 'character'
                                                        inner join map_character_locations l on l.characterid = c.id
                                                        left join ( select  m.*
                                                                    from    esi_fleet f
                                                                        inner join esi_fleet_member m on m.fleetid = f.id
                                                                    where   f.active > 0
                                                            ) f on f.characterid = c.id
                                                    where   f.fleetid is null
                                                    and     l.lastdate between ? and ?
                                                    order by l.online desc, l.lastdate asc
                                                    limit ".$crestLimit
                        , [ date("Y-m-d H:i:s", strtotime("now")-600),
                            date("Y-m-d H:i:s", strtotime("now")-$crestTimer)]))
            {
                $characterIDs = [];
                foreach ($results as $result)
                {
                    \AppRoot::doCliOutput("> [".$result["id"]."] ".$result["name"]. " ".(($result["online"])?"Online":"Offline"));

                    // Update datum bijwerken om dubbele execution te voorkomen
                    $character = new \esi\model\Character($result["id"]);
                    $character->setLocation(($result["solarsystemid"])?:null, ($result["shiptypeid"])?:null, ($result["online"])?true:false);
                    $characterIDs[] = $result["id"];

                    if (count($characterIDs) == 5) {
                        $args = array_merge(["esi", "location", "character"], $characterIDs);
                        \AppRoot::runCron($args);
                        $characterIDs = [];
                    }
                }

                if (count($characterIDs) > 0) {
                    $args = array_merge(["esi", "location", "character"], $characterIDs);
                    \AppRoot::runCron($args);
                }
            }

            // Einde loop.
            \AppRoot::doCliOutput("Running for ".\AppRoot::getExecTime()." seconds");
            sleep(1);
        }
        \AppRoot::doCliOutput("Finished run!");
    }

    function doCharacter($arguments=[])
    {
        \AppRoot::doCliOutput("Get character locations");
        $locationTracker = new \map\controller\LocationTracker();

        while (count($arguments) > 0)
        {
            $authGroups = [];
            $character = new \esi\model\Character(array_shift($arguments));
            \AppRoot::doCliOutput("> ".$character->name);

            $userSession = (\User::getUSER())?true:false;
            if ($character->getUser())
                $authGroups = $character->getUser()->getAuthGroups();
            if (count($authGroups) == 0) {
                \AppRoot::doCliOutput("No authgroup for ".$character->name);
                continue;
            }

            $isOnline = false;
            $solarSystemID = null;
            $shipTypeID = null;

            $api = new \esi\Api();
            $api->setToken($character->getToken());

            // Check online
            $api->get("v2/characters/".$character->id."/online/");
            if ($api->success()) {
                $isOnline = ($api->getResult()->online)?true:false;
            }

            if (!$isOnline) {
                // Offline..?
                $character->setOffline();
                \AppRoot::doCliOutput($character->name." is not logged in!");
                continue;
            }

            // Locatie ophalen
            $api->get("v1/characters/".$character->id."/location/");
            if ($api->success()) {
                if (isset($api->getResult()->solar_system_id) && $api->getResult()->solar_system_id) {
                    if (!$userSession)
                        \User::setUSER($character->getUser());

                    $solarSystemID = (int)$api->getResult()->solar_system_id;
                    $api->get("v1/characters/".$character->id."/ship/");
                    if ($api->success()) {
                        if (isset($api->getResult()->ship_type_id) && $api->getResult()->ship_type_id) {
                            $shipTypeID = $api->getResult()->ship_type_id;
                        }
                    }

                    $locationTracker->setCharacterLocation($character, $solarSystemID, $shipTypeID);
                    if (!$userSession)
                        \User::unsetUser();
                } else {
                    // Offline..?
                    $character->setLocation(null, null, false);
                    \AppRoot::doCliOutput("No result from CREST. Is ".$character->name." logged in?");
                    continue;
                }
            } else {
                \AppRoot::doCliOutput("CREST call failed. Returned ".$api->httpStatus);
                continue;
            }
            $api->closeCurl();
        }

        return;
    }
}
