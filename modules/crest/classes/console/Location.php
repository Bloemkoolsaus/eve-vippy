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

            $i = 0;
            if ($results = \MySQL::getDB()->getRows("select 1 as online, c.*, l.lastdate as lastupdate
                                                    from    characters c
                                                        inner join crest_token t on t.tokenid = c.id and t.tokentype = 'character'
                                                        inner join map_character_locations l on l.characterid = c.id
                                                    where   l.lastdate < ?
                                                union
                                                    select  0 as online, c.*, cl.lastupdate
                                                    from    characters c
                                                        inner join crest_token t on t.tokenid = c.id and t.tokentype = 'character'
                                                        left join map_character_locations l on l.characterid = c.id
                                                        left join crest_character_location cl on cl.characterid = c.id
                                                    where   l.characterid is null
                                                    and    (cl.lastupdate is null or cl.lastupdate < ?)
                                                order by online desc, lastupdate asc, updatedate desc
                                                limit 50"
                        , [ date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s")-11,date("m"),date("d"),date("Y"))),
                            date("Y-m-d H:i:s", mktime(date("H"),date("i")-10,date("s"),date("m"),date("d"),date("Y")))]))
            {
                foreach ($results as $result)
                {
                    // Asynchroon uitvoeren
                    \AppRoot::doCliOutput("> [".$result["id"]."] ".$result["name"]);
                    $command = "php ".getcwd()."/cron.php crest location character ".$result["id"]." > /dev/null &";
                    \AppRoot::doCliOutput($command);
                    exec($command);
                    $i++;
                }
            }


            \AppRoot::doCliOutput("Running for ".\AppRoot::getExecTime()." seconds");
            sleep(1);
        }
        \AppRoot::doCliOutput("Timeout!");

        // Online characters opruimen
        \MySQL::getDB()->doQuery("delete from map_character_locations where lastdate < ?", [date("Y-m-d H:i:s", mktime(date("H"),date("i")-10,date("s"),date("m"),date("d"),date("Y")))]);
    }

    function doCharacter($arguments=[])
    {
        $errors = [];
        $results = [];
        $authGroup = null;
        $character = new \crest\model\Character(array_shift($arguments));
        if ($character->getUser())
            $authGroup = $character->getUser()->getCurrentAuthGroup();
        if (!$authGroup)
            $errors[] = "No authgroup for ".$character->name;

        $solarSystem = null;
        if (count($errors) == 0)
        {
            // Locatie ophalen
            $crest = new \crest\Api();
            $crest->setToken($character->getToken());
            $crest->get("characters/".$character->id."/location/");

            if ($crest->success())
            {
                if (isset($crest->getResult()->solarSystem))
                {
                    \User::setUSER($character->getUser());
                    $solarSystem = \map\model\SolarSystem::findById($crest->getResult()->solarSystem->id);
                    $locationTracker = new \map\controller\LocationTracker();
                    $locationTracker->setCharacterLocation($authGroup->id, $character->id, $solarSystem->id);
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

                    $session = "crest-".(($character->getUser())?$character->getUser()->id:$character->id)."-".date("Ymd");
                    $character->getUser()->addLog("ingame", $character->id, null, $character->id, $session);
                    \User::unsetUser();
                }
                else
                {
                    // Offline..?
                    $errors[] = "No result from CREST. Is ".$character->name." logged in?";
                }
            } else
                $errors[] = "CREST call failed. Returned ".$crest->httpStatus;
        }

        // Last location check date bijwerken.
        \MySQL::getDB()->updateinsert("crest_character_location", [
            "characterid" => $character->id,
            "solarsystemid" => ($solarSystem)?$solarSystem->id:null,
            "remark" => (count($errors) > 0)?json_encode($errors):$solarSystem->name,
            "lastupdate" => date("Y-m-d H:i:s")
        ], [
            "characterid" => $character->id
        ]);

        // Kon locatie niet ophalen. Uit lijst met 'actieve' toons halen
        if (count($errors) > 0) {
            \MySQL::getDB()->delete("map_character_locations", ["characterid" => $character->id]);
            return ["errors" => $errors];
        }
        return $results;
    }
}