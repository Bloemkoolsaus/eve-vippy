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
            if ($results = \MySQL::getDB()->getRows("select 1 as online, c.*, l.lastdate as requestdate
                                                    from    characters c
                                                        inner join crest_token t on t.tokenid = c.id and t.tokentype = 'character'
                                                        inner join map_character_locations l on l.characterid = c.id
                                                    where   l.lastdate < ?
                                                union
                                                    select  0 as online, c.*, clog.requestdate
                                                    from    characters c
                                                        inner join crest_token t on t.tokenid = c.id and t.tokentype = 'character'
                                                        left join map_character_locations l on l.characterid = c.id
                                                        left join (select   max(requestdate) as requestdate, url
                                                                    from    crest_log
                                                                    group by url
                                                            ) clog on clog.url = concat('characters/', c.id, '/location/')
                                                    where   l.characterid is null
                                                    and    (clog.requestdate is null or clog.requestdate < ?)
                                                order by online desc, requestdate asc, updatedate desc
                                                limit 100"
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
        $character = new \crest\model\Character(array_shift($arguments));
        $authGroup = null;
        if ($character->getUser())
            $authGroup = $character->getUser()->getCurrentAuthGroup();
        if (!$authGroup)
            $errors[] = "No authgroup for ".$character->name;

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
                    $errors[] = "No result from CREST. Is ".$character->name." logged in?";
                }
            } else
                $errors[] = "CREST call failed. Returned ".$crest->httpStatus;
        }

        // Kon locatie niet ophalen. Uit lijst met 'actieve' toons halen
        if (count($errors) > 0)
            \MySQL::getDB()->delete("map_character_locations", ["characterid" => $character->id]);

        return ["errors" => $errors];
    }
}