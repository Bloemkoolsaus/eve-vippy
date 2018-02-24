<?php
namespace esi\view;

class Character
{
    function getLocation($arguments=[])
    {
        $errors = [];
        $character = new \esi\model\Character(array_shift($arguments));

        if ($character->getToken())
        {
            // Laatst bekende locatie ophalen
            $location = $character->getLocation();
            if ($location) {
                if ($location->lastdate <= strtotime("now")-11) {
                    $location = null;
                }
            }

            if (!$location) {
                // CREST call
                $console = new \esi\console\Location();
                $console->doCharacter([$character->id]);
            }

            $location = $character->getLocation();
            if ($location) {
                $solarSystem = \map\model\SolarSystem::findById($location->solarsystemID);
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
        } else
            $errors[] = "No (valid) CREST token found for ".$character->name;

        if (count($errors) == 0) {
            $errors[] = "Is ".$character->name." logged in?";
        }

        return json_encode(["errors" => $errors]);
    }

    function getDestination($arguments=[])
    {
        $errors = [];
        $character = new \esi\model\Character(array_shift($arguments));
        $solarSystem = new \map\model\SolarSystem(array_shift($arguments));

        if ($solarSystem)
        {
            if ($character->getToken())
            {
                $api = new \esi\Api();
                $api->setToken($character->getToken());
                $api->post("v2/ui/autopilot/waypoint/", [
                    "clear_other_waypoints" => true,
                    "add_to_beginning" => true,
                    "destination_id" => (int)$solarSystem->id
                ]);

                if ($api->success()) {
                    return json_encode(["destination" => [
                        "id" => $solarSystem->id,
                        "name" => $solarSystem->name
                    ]]);
                } else {
                    $errors[] = "ESI call failed. Returned ".$api->httpStatus;
                    if ($api->getResult()) {
                        if (isset($api->getResult()->message))
                            $errors[] = $api->getResult()->message;
                        if (isset($api->getResult()->exceptionType)) {
                            if ($api->getResult()->exceptionType == "UnauthorizedError") {
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