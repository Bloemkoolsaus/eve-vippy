<?php
namespace crest\console;

class Location
{
    function fetchCharacter($characterID)
    {
        // Asynchroon uitvoeren
        \AppRoot::runCron(["crest", "location", "character", $characterID]);
    }

    function fetchCharacters($characterIDs)
    {
        // Asynchroon uitvoeren
        $arguments = ["crest", "location", "character"];
        foreach ($characterIDs as $id) {
            $arguments[] = $id;
        }
        \AppRoot::runCron($arguments);
    }

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
                                                        inner join users u on u.id = c.userid and u.isvalid > 0
                                                        inner join crest_token t on t.tokenid = c.id and t.tokentype = 'character'
                                                        inner join map_character_locations l on l.characterid = c.id
                                                        left join ( select  m.*
                                                                    from    crest_fleet f
                                                                        inner join crest_fleet_member m on m.fleetid = f.id
                                                                    where   f.active > 0
                                                            ) f on f.characterid = c.id
                                                    where   f.fleetid is null
                                                    and     l.lastdate < ? and l.lastdate > ?
                                                    order by l.online desc, l.lastdate asc
                                                    limit ".$crestLimit
                                    , [ date("Y-m-d H:i:s", strtotime("now")-$crestTimer),
                                        date("Y-m-d H:i:s", strtotime("now")-300)]))
            {
                $characterIDs = [];
                foreach ($results as $result)
                {
                    \AppRoot::doCliOutput("> [".$result["id"]."] ".$result["name"]. " ".(($result["online"])?"ONline":"OFFline"));

                    // Update datum bijwerken om dubbele execution te voorkomen
                    $character = new \crest\model\Character($result["id"]);
                    $character->setLocation(($result["solarsystemid"])?:null, ($result["shiptypeid"])?:null, ($result["online"])?true:false);
                    $characterIDs[] = $result["id"];

                    if (count($characterIDs) == 5) {
                        $this->fetchCharacters($characterIDs);
                        $characterIDs = [];
                    }
                }

                if (count($characterIDs) > 0)
                    $this->fetchCharacters($characterIDs);
            }

            // Einde loop.
            \AppRoot::doCliOutput("Running for ".\AppRoot::getExecTime()." seconds");
            sleep(1);
        }
        \AppRoot::doCliOutput("Finished run!");
    }

    function doCharacter($arguments=[])
    {
        $results = [];
        $crest = new \crest\Api();
        $locationTracker = new \map\controller\LocationTracker();

        while (count($arguments) > 0)
        {
            $authGroups = [];
            $character = new \crest\model\Character(array_shift($arguments));
            $userSession = (\User::getUSER())?true:false;

            \AppRoot::doCliOutput("Location->doCharacter($character->name)");
            if ($character->getUser())
                $authGroups = $character->getUser()->getAuthGroups();
            if (count($authGroups) == 0) {
                \AppRoot::doCliOutput("No authgroup for ".$character->name);
                return "No authgroup for ".$character->name;
            }

            // Locatie ophalen
            $crest->setToken($character->getToken());
            $crest->get("characters/".$character->id."/location/");
            if ($crest->success()) {
                if (isset($crest->getResult()->solarSystem)) {
                    if (!$userSession)
                        \User::setUSER($character->getUser());
                    $solarSystem = \map\model\SolarSystem::findById((int)$crest->getResult()->solarSystem->id);
                    $locationTracker->setCharacterLocation($character, $solarSystem->id);
                    $results = [
                        "system" => [
                            "id" => $solarSystem->id,
                            "name" => $solarSystem->name
                        ],
                        "character" => [
                            "id" => $character->id,
                            "name" => $character->name
                        ]
                    ];
                    if (!$userSession)
                        \User::unsetUser();
                } else {
                    // Offline..?
                    $character->setLocation(null, null, false);
                    \AppRoot::doCliOutput("No result from CREST. Is ".$character->name." logged in?");
                    $results["errors"][] = "No result from CREST. Is ".$character->name." logged in?";
                }
            } else {
                \AppRoot::doCliOutput("CREST call failed. Returned ".$crest->httpStatus);
                $results["errors"][] = "CREST call failed. Returned ".$crest->httpStatus;
            }
        }

        $crest->closeCurl();
        return $results;
    }
}
