<?php
namespace crest\console;

class Fleet
{
    function doFleets($arguments=[])
    {
        \AppRoot::setMaxExecTime(60);
        \AppRoot::setMaxMemory("2G");
        \AppRoot::doCliOutput("doFleet(".implode(",",$arguments).")");

        // Oude fleets opruimen (ouder dan 6u)
        \MySQL::getDB()->doQuery("delete from crest_fleet where active = 0 and lastupdate < ?", [date("Y-m-d H:i:s", mktime(date("H")-6,date("i"),date("s"),date("m"),date("d"),date("Y")))]);

        // Als we tegen de timeout aanlopen, afbreken
        while (!\AppRoot::approachingMaxExecTime(2))
        {
            \AppRoot::doCliOutput("Find fleets");
            if ($results = \MySQL::getDB()->getRows(" select  *
                                                      from    crest_fleet
                                                      where   active > 0
                                                      and     (lastupdate < ? or lastupdate is null)"
                                   , [date("Y-m-d H:i:s", strtotime("now")-31)]))
            {
                \AppRoot::doCliOutput(count($results)." fleets found");
                foreach ($results as $result)
                {
                    \AppRoot::doCliOutput("fleet: ".$result["id"]);
                    $fleet = new \fleets\model\Fleet();
                    $fleet->load($result);
                    $this->getFleetMembers($fleet);
                }
            }

            \AppRoot::doCliOutput("Running for ".\AppRoot::getExecTime()." seconds");
            sleep(1);
            if (\AppRoot::doDebug())
                break;
        }
        \AppRoot::doCliOutput("Timeout!");
    }

    function getFleetMembers(\fleets\model\Fleet $fleet)
    {
        \AppRoot::doCliOutput("getFleetMembers($fleet->id)");
        // Zet update date alvast, zodat we geen dubbele executies krijgen voor deze fleet.
        $fleet->lastUpdate = date("Y-m-d H:i:s", strtotime("now")+1);
        $fleet->store();

        if (!$fleet->getBoss()) {
            $fleet->active = 0;
            $fleet->statusMessage = "Fleet boss not found";
            \AppRoot::doCliOutput("Fleet boss not found");
            return $fleet;
        }

        if (!$fleet->getBoss()->getToken()) {
            $fleet->active = 0;
            $fleet->statusMessage = "Fleet boss does not have a valid CREST token";
            \AppRoot::doCliOutput("Fleet boss does not have a valid CREST token");
            return $fleet;
        }

        $fleet->active = false;
        $fleet->statusMessage = null;

        if ($fleet->id == 0)
        {
            $fleet->active = 0;
            $fleet->statusMessage = "Cannot call CREST, no fleet ID.";
            $fleet->store();
        }
        else
        {
            $crest = new \crest\Api();
            $crest->setToken($fleet->getBoss()->getToken());
            $crest->get("fleets/".$fleet->id."/members/");

            if ($crest->success())
            {
                \AppRoot::debug($crest->getResult());
                $locationTracker = new \map\controller\LocationTracker();
                if (isset($crest->getResult()->items))
                {
                    foreach ($crest->getResult()->items as $fleetMember)
                    {
                        $character = new \eve\model\Character($fleetMember->character->id);
                        \AppRoot::doCliOutput(" - ".$character->name);
                        if ($character->getUser())
                            \User::setUSER($character->getUser());

                        $locationTracker->setCharacterLocation(
                            $fleet->authGroupID,
                            $fleetMember->character->id,
                            $fleetMember->solarSystem->id,
                            $fleetMember->ship->id
                        );
                        \User::unsetUser();
                    }

                    $fleet->active = 1;
                    $fleet->statusMessage = "Tracking ".count($crest->getResult()->items)." fleet members.";
                    $fleet->store();
                }
                else
                {
                    \AppRoot::doCliOutput("Could not parse anwser received from CREST. HELP....", "red");
                    $fleet->active = 0;
                    $fleet->statusMessage = "Could not parse anwser received from CREST. HELP....";
                    $fleet->store();
                }
            }
            else
            {
                if ($crest->httpStatus >= 500 and $crest->httpStatus < 600)
                {
                    \AppRoot::doCliOutput("CREST call failed, Is CREST down??  Retrying in 5 minutes.", "red");
                    $fleet->active = 1;
                    $fleet->statusMessage = "CREST call failed, Is CREST down??  Retrying in 5 minutes.";
                    $fleet->lastUpdate = date("Y-m-d H:i:s", strtotime("now")+(60*5));
                    $fleet->store();
                }
                else
                {
                    \AppRoot::doCliOutput("Failed to call CREST for fleet info. ".$crest->httpStatus." returned!", "red");
                    $fleet->active = 0;
                    $fleet->statusMessage = "Failed to call CREST for fleet info. ".$crest->httpStatus." returned!";
                    $fleet->store();
                }
            }

        }

        return $fleet;
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