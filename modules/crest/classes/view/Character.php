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
                // Rate limit
                if (strtotime($current["lastdate"]) > strtotime("now")-30)
                    return json_encode(["system" => $current["solarsystemid"]]);

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
                    $locationTracker = new \map\controller\LocationTracker();
                    $locationTracker->setCharacterLocation($authGroupID, $character->id, $crest->getResult()->solarSystem->id, $shipTypeID);
                    return json_encode(["system" => $crest->getResult()->solarSystem->id]);
                }
                else
                    $errors[] = "No result from CREST. Is ".$character->name." logged in?";
            }
            else
                $errors[] = "CREST call failed. Returned ".$crest->httpStatus;
        }
        else
            $errors[] = "No (valid) CREST token found";

        return json_encode(["errors" => $errors]);
    }
}