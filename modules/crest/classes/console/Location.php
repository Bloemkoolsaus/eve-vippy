<?php
namespace crest\console;

class Location
{
    function doDefault($arguments=[])
    {
        \AppRoot::setMaxExecTime(60);
        \AppRoot::setMaxMemory("2G");
        \AppRoot::doCliOutput("doLocations(".implode(",",$arguments).")");

        // Als we tegen de timeout aanlopen, afbreken
        while (!\AppRoot::approachingMaxExecTime(5))
        {
            \AppRoot::doCliOutput("Find characters");

            if ($results = \MySQL::getDB()->getRows("select c.*
                                                    from    characters c
                                                        inner join crest_token t on t.tokenid = c.id and t.tokentype = 'character'
                                                        inner join map_character_locations l on l.characterid = c.id
                                                    where   l.lastdate < ?
                                                    order by l.lastdate asc"
                        , [date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s")-11,date("m"),date("d"),date("Y")))]))
            {
                foreach ($results as $result)
                {
                    // Asynchroon uitvoeren
                    \AppRoot::doCliOutput("> [".$result["id"]."] ".$result["name"]);
                    exec(getcwd()."/cron.php crest location character ".$result["id"]." > /dev/null &");
                }
            }

            \AppRoot::doCliOutput("Running for ".\AppRoot::getExecTime()." seconds");
            sleep(1);
        }
        \AppRoot::doCliOutput("Timeout!");
    }

    function doCharacter($arguments=[])
    {
        $character = new \crest\model\Character(array_shift($arguments));
        $authGroup = null;
        if ($character->getUser())
            $authGroup = $character->getUser()->getCurrentAuthGroup();
        if (!$authGroup)
            return ["errors" => "No authgroup for ".$character->name];

        // Locatie ophalen
        $crest = new \crest\Api();
        $crest->setToken($character->getToken());
        $crest->get("characters/".$character->id."/location/");

        if ($crest->success())
        {
            if (isset($crest->getResult()->solarSystem))
            {
                $solarSystem = \map\model\SolarSystem::findById($crest->getResult()->solarSystem->id);
                $locationTracker = new \map\controller\LocationTracker();
                $locationTracker->setCharacterLocation($authGroup->id, $character->id, $solarSystem->id);
                return [
                    "system" => [
                        "id" => $solarSystem->id,
                        "name" => $solarSystem->name
                    ],
                    "character" => [
                        "id" => $character->id,
                        "name" => $character->name
                    ]
                ];
            }
            else
            {
                // Offline..?
                \MySQL::getDB()->delete("map_character_locations", ["characterid" => $character->id]);
                $errors[] = "No result from CREST. Is ".$character->name." logged in?";
            }
        }
        else
            $errors[] = "CREST call failed. Returned ".$crest->httpStatus;

        return ["errors" => $errors];
    }
}