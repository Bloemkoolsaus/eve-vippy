<?php
namespace crest\view;

class Character
{
    function getLocation($arguments=[])
    {
        $errors = [];
        $character = new \crest\model\Character(array_shift($arguments));

        if ($character->getToken())
        {
            $shipTypeID = 0;
            $authGroupID = \User::getUSER()->getCurrentAuthGroupID();

            // Laatst bekende locatie ophalen
            $current = \MySQL::getDB()->getRow("select * from map_character_locations where characterid = ?", [$character->id]);
            if ($current)
            {
                // Rate limit 5 seconden
                if (strtotime($current["lastdate"]) > strtotime("now")-15)
                {
                    $solarSystem = \map\model\SolarSystem::findById($current["solarsystemid"]);
                    return json_encode([
                        "system" => [
                            "id" => $solarSystem->id,
                            "name" => $solarSystem->name
                        ],
                        "character" => [
                            "id" => $character->id,
                            "name" => $character->name
                        ]
                    ]);
                }

                $shipTypeID = $current["shiptypeid"];
                $authGroupID = $current["authgroupid"];
            }

            // CREST call
            $crest = new \crest\Api();
            $crest->setToken($character->getToken());
            $crest->get("characters/".$character->id."/location/");
            if ($crest->success())
            {
                if (isset($crest->getResult()->solarSystem))
                {
                    $solarSystem = \map\model\SolarSystem::findById($crest->getResult()->solarSystem->id);
                    $locationTracker = new \map\controller\LocationTracker();
                    $locationTracker->setCharacterLocation($authGroupID, $character->id, $solarSystem->id, $shipTypeID);
                    return json_encode([
                        "system" => [
                            "id" => $solarSystem->id,
                            "name" => $solarSystem->name
                        ],
                        "character" => [
                            "id" => $character->id,
                            "name" => $character->name
                        ]
                    ]);
                } else
                    $errors[] = "No result from CREST. Is ".$character->name." logged in?";
            } else
                $errors[] = "CREST call failed. Returned ".$crest->httpStatus;
        } else
            $errors[] = "No (valid) CREST token found for ".$character->name;

        return json_encode(["errors" => $errors]);
    }

    function getDestination($arguments=[])
    {
        $errors = [];
        $character = new \crest\model\Character(array_shift($arguments));
        $solarSystem = new \map\model\SolarSystem(array_shift($arguments));

        if ($solarSystem)
        {
            if ($character->getToken())
            {
                $crest = new \crest\Api();
                $crest->setToken($character->getToken());
                $crest->post("characters/".$character->id."/ui/autopilot/waypoints/", [
                    "clearOtherWaypoints" => true,
                    "first" => true,
                    "solarSystem" => [
                        "id" => (int)$solarSystem->id,
                        "href" => "https://crest-tq.eveonline.com/solarsystems/".$solarSystem->id."/"
                    ]
                ]);

                if ($crest->success()) {
                    return json_encode(["destination" => [
                        "id" => $solarSystem->id,
                        "name" => $solarSystem->name
                    ]]);
                }
                else
                {
                    $errors[] = "CREST call failed. Returned ".$crest->httpStatus;
                    if ($crest->getResult())
                    {
                        $extraMsg = [];
                        if (isset($crest->getResult()->message))
                            $errors[] = $crest->getResult()->message;
                        if (isset($crest->getResult()->exceptionType)) {
                            if ($crest->getResult()->exceptionType == "UnauthorizedError") {
                                $errors[] = "<br />It seems your CREST token for ".$character->name." is no longer valid.";
                                $errors[] = "Please refresh it by logging in the SSO from your profile page.";
                            }
                        }
                    }
                }
            } else
                $errors[] = "No (valid) CREST token found for ".$character->name;
        } else
            $errors[] = "Solarsystem not found";

        return json_encode(["errors" => $errors]);
    }
}