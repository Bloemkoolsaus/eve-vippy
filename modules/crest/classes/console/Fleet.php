<?php
namespace crest\console;

class Fleet
{
    function doDefault($arguments=[])
    {
        \AppRoot::setMaxExecTime(60);
        \AppRoot::setMaxMemory("2G");
        \AppRoot::doCliOutput("doFleet(".implode(",",$arguments).")");
        $crestTimer = (int)((\Config::getCONFIG()->get("crest_location_timer"))?:5);

        // Als we tegen de timeout aanlopen, afbreken
        while (\AppRoot::getExecTime() < 58)
        {
            \AppRoot::doCliOutput("Find fleets");
            if ($results = \MySQL::getDB()->getRows("select id from crest_fleet 
                                                     where active > 0 
                                                     and (lastupdate < ? or lastupdate is null)"
                                        , [date("Y-m-d H:i:s", strtotime("now")-$crestTimer)]))
            {
                \AppRoot::doCliOutput(count($results)." fleets found");
                foreach ($results as $result)
                {
                    \AppRoot::doCliOutput("fleet: ".$result["id"]);
                    \MySQL::getDB()->doQuery("update crest_fleet set lastupdate = '".date("Y-m-d H:i:s")."' where id = ".$result["id"]);
                    \AppRoot::runCron(["crest", "fleet", "fleet", $result["id"]]);
                }
            }

            \AppRoot::doCliOutput("Running for ".\AppRoot::getExecTime()." seconds");
            sleep(1);
        }
        \AppRoot::doCliOutput("Finished run!");
    }

    function doFleet($arguments=[])
    {
        $crest = new \crest\Api();
        while (count($arguments) > 0) {
            $fleet = \fleets\model\Fleet::findById(array_shift($arguments));
            if ($fleet)
                $this->getFleetMembers($fleet, $crest);
        }
        $crest->closeCurl();
    }

    function getFleetMembers(\fleets\model\Fleet $fleet, \crest\Api $crest=null)
    {
        \AppRoot::doCliOutput("getFleetMembers($fleet->id)");
        if (!$fleet->id) {
            $fleet->active = false;
            $fleet->statusMessage = "Cannot call CREST, no fleet ID.";
            $fleet->store();
            return $fleet;
        }

        // Zet update date alvast, zodat we geen dubbele executies krijgen voor deze fleet.
        $fleet->lastUpdate = date("Y-m-d H:i:s", strtotime("now")+1);
        $fleet->store();

        if (!$fleet->getBoss()) {
            $fleet->active = false;
            $fleet->statusMessage = "Fleet boss not found";
            \AppRoot::doCliOutput("Fleet boss not found");
            return $fleet;
        }

        if (!$fleet->getBoss()->getToken()) {
            $fleet->active = false;
            $fleet->statusMessage = "Fleet boss does not have a valid CREST token";
            \AppRoot::doCliOutput("Fleet boss does not have a valid CREST token");
            return $fleet;
        }

        $fleet->active = false;
        $fleet->statusMessage = null;
        $fleetMembers = [];
        $userSession = (\User::getUSER())?true:false;

        if (!$crest)
            $crest = new \crest\Api();

        $crest->setToken($fleet->getBoss()->getToken());
        $crest->get("fleets/".$fleet->id."/members/");

        if ($crest->success())
        {
            \AppRoot::debug($crest->getResult());
            $locationTracker = new \map\controller\LocationTracker();

            if (isset($crest->getResult()->items))
            {
                // Dubbele execution voorkomen
                foreach ($crest->getResult()->items as $fleetMember) {
                    $fleetMembers[] = [
                        (int)$fleet->id,
                        (int)$fleetMember->character->id,
                        (int)$fleetMember->wingID,
                        (int)$fleetMember->squadID,
                        (int)$fleetMember->solarSystem->id,
                        (int)$fleetMember->ship->id,
                        (int)($fleetMember->takesFleetWarp)?1:0
                    ];
                }
                // Reset fleet members
                \MySQL::getDB()->doQuery("delete from crest_fleet_member where fleetid = ?", [$fleet->id]);
                if (count($fleetMembers) > 0) {
                    $memberQuery = [];
                    foreach ($fleetMembers as $member) {
                        $memberQuery[] = "(".implode(", ", $member).")";
                    }
                    \MySQL::getDB()->doQuery("insert into crest_fleet_member (fleetid, characterid, wingid, squadid, solarsystemid, shiptypeid, takewarp) values ".implode(", ", $memberQuery));
                }

                foreach ($crest->getResult()->items as $fleetMember)
                {
                    // Check character exists
                    /** @var \crest\model\Character $character */
                    $character = \crest\model\Character::findByID($fleetMember->character->id);
                    if (!$character) {
                        $controller = new \eve\controller\Character();
                        $controller->importCharacter($fleetMember->character->id);
                        $character = new \crest\model\Character((int)$fleetMember->character->id);
                    }
                    \AppRoot::doCliOutput(" - ".$character->name);
                    if (!$userSession)
                        \User::setUSER($character->getUser());

                    // Location tracker
                    $locationTracker->setCharacterLocation($character, (int)$fleetMember->solarSystem->id, (int)$fleetMember->ship->id);
                    if (!$userSession)
                        \User::unsetUser();
                }

                $fleet->active = true;
                $fleet->statusMessage = "Tracking ".count($crest->getResult()->items)." fleet members.";
                $fleet->store();
            } else {
                \AppRoot::doCliOutput("Could not parse result received from CREST. HELP....", "red");
                $fleet->active = false;
                $fleet->statusMessage = "Could not parse result received from CREST. HELP....";
                $fleet->store();
            }
        } else {
            if ($crest->httpStatus >= 500 and $crest->httpStatus < 600) {
                \AppRoot::doCliOutput("CREST call failed, Is CREST down??  Retrying in 5 minutes.", "red");
                $fleet->active = false;
                $fleet->statusMessage = "CREST call failed, Is CREST down??  Retrying in 5 minutes.";
                $fleet->lastUpdate = date("Y-m-d H:i:s", strtotime("now")+(60*5));
                $fleet->store();
            } else {
                \AppRoot::doCliOutput("Failed to call CREST for fleet info. ".$crest->httpStatus." returned!", "red");
                $fleet->active = false;
                $fleet->statusMessage = "Failed to call CREST for fleet info. ".$crest->httpStatus." returned!";
                $fleet->store();
            }
        }

        unset($crest);
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

        // ESI link?
        if (strpos($url, "esi.tech.ccp") !== false) {
            $parts = explode("/", $url);
            while (count($parts) > 0) {
                $part = array_shift($parts);
                if ($part == "fleets") {
                    $url = "fleets/".array_shift($parts)."/";
                    break;
                }
            }
        }

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
