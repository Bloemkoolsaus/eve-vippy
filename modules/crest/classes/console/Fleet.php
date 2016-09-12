<?php
namespace crest\console;

class Fleet
{
    function doFleet($arguments=[])
    {
        $fleetURL = "fleets/1047511263687/";

        $character = new \crest\model\Character(1899584653);

        $crest = new \crest\Api();
        $crest->setToken($character->getToken());
        $crest->get($fleetURL);

        if ($crest->success())
        {
            if (isset($crest->getResult()->members))
            {
                $url = str_replace($crest->baseURL, "", $crest->getResult()->members->href);
                $crest = new \crest\Api();
                $crest->setToken($character->getToken());
                $crest->get($url);

                if ($crest->success())
                {
                    foreach ($crest->getResult()->items as $fleetMember)
                    {
                        echo "<pre>".print_r($fleetMember, true)."</pre>";

                        \MySQL::getDB()->updateinsert("mapwormholecharacterlocations", [
                            "characterid" => $fleetMember->character->id,
                            "solarsystemid" => $fleetMember->solarSystem->id,
                            "shiptypeid" => $fleetMember->ship->id,
                            "lastdate" => date("Y-m-d H:i:s")
                        ],[
                            "characterid" => $fleetMember->character->id
                        ]);
                    }
                }
            }

        }
    }
}