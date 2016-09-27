<?php
namespace crest\console;

class Fleet
{
    function doFleet($arguments=[])
    {
        \AppRoot::setMaxExecTime(60);
        \AppRoot::doCliOutput("doFleet(".implode(",",$arguments).")");
        $locationTracker = new \map\controller\LocationTracker();

        // Als we tegen de timeout aanlopen, afbreken
        while (!\AppRoot::approachingMaxExecTime(2))
        {
            if ($results = \MySQL::getDB()->getRows(" select  *
                                                      from    crest_fleet
                                                      where   active > 0
                                                      and     updatedate < ?"
                                        , [date("Y-m-d H:i:s", strtotime("now")-2)]))
            {
                foreach ($results as $result)
                {
                    $fleet = new \crest\model\Fleet();
                    $fleet->load($result);

                    if (!$fleet->getBoss()) {
                        $fleet->active = 0;
                        $fleet->statusMessage = "Fleet boss not found";
                        continue;
                    }

                    if (!$fleet->getBoss()->getToken()) {
                        $fleet->active = 0;
                        $fleet->statusMessage = "Fleet boss does not have a valid CREST token.";
                        continue;
                    }

                    // Zet update date alvast, zodat we geen dubbele executies krijgen voor deze fleet.
                    $fleet->lastUpdate = date("Y-m-d H:i:s", strtotime("now")+1);
                    $fleet->store();

                    $fleet->active = 0;
                    $fleet->statusMessage = "";

                    $crest = new \crest\Api();
                    $crest->setToken($fleet->getBoss()->getToken());
                    $crest->get("fleets/".$fleet->id."/members/");

                    if ($crest->success())
                    {
                        if (isset($crest->getResult()->items))
                        {
                            foreach ($crest->getResult()->items as $fleetMember)
                            {
                                $locationTracker->setCharacterLocation(
                                    $fleet->authGroupID,
                                    $fleetMember->character->id,
                                    $fleetMember->solarSystem->id,
                                    $fleetMember->ship->id
                                );
                            }

                            $fleet->active = 1;
                            $fleet->store();

                            // Trigger map update
                            \MySQL::getDB()->update("mapwormholechains",
                                ["lastmapupdatedate" => date("Y-m-d H:i:s", strtotime("now"))],
                                ["authgroupid" => $fleet->authGroupID]
                            );
                        }
                        else
                        {
                            $fleet->active = 0;
                            $fleet->statusMessage = "Could not parse anwser received from CREST. HELP....";
                            $fleet->store();
                        }
                    }
                    else
                    {
                        if ($crest->httpStatus >= 500 and $crest->httpStatus <= 600)
                        {
                            $fleet->active = 1;
                            $fleet->statusMessage = "CREST call failed, Is CREST down??  Retrying in 5 minutes.";
                            $fleet->lastUpdate = date("Y-m-d H:i:s", strtotime("now")+(60*5));
                            $fleet->store();
                        }
                        else
                        {
                            $fleet->active = 0;
                            $fleet->statusMessage = "Failed to call CREST for fleet info. ".$crest->httpStatus." returned!";
                            $fleet->store();
                        }
                    }
                }
            }
        }

        /** @var \crest\model\Fleet $fleet */
        $fleet = \crest\model\Fleet::findById(array_shift($arguments));
        if (!$fleet)
            return null;

    }

    /**
     * get fleet
     * @param $url
     * @param $characterID
     * @return \crest\model\Fleet|null
     */
    function getFleetByURL($url, $characterID)
    {
        $character = new \crest\model\Character($characterID);

        $crest = new \crest\Api();
        $crest->setToken($character->getToken());

        $url = str_replace($crest->baseURL, "", $url);
        $crest->get($url);

        if ($crest->success())
        {
            $parts = explode("/",$url);
            $id = $parts[1];

            /** @var \crest\model\Fleet $fleet */
            $fleet = \crest\model\Fleet::findOne(["id" => $id]);
            if (!$fleet) {
                $fleet = new \crest\model\Fleet();
                $fleet->id = $id;
            }
            $fleet->bossID = $characterID;
            $fleet->authGroupID = \User::getUSER()->getCurrentAuthGroupID();
            $fleet->active = 1;
            $fleet->store();
            return $fleet;
        }

        return null;
    }
}