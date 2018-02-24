<?php
namespace esi\console;

class Fleet
{
    function doDefault($arguments=[])
    {
        \AppRoot::setMaxExecTime(60);
        \AppRoot::setMaxMemory("2G");
        \AppRoot::doCliOutput("doFleet(".implode(",",$arguments).")");
        $crestTimer = (int)((\Config::getCONFIG()->get("crest_location_timer"))?:5);

        // Als we tegen de timeout aanlopen, afbreken
        while (\AppRoot::getExecTime() < 58) {
            \AppRoot::doCliOutput("Find fleets");
            if ($results = \MySQL::getDB()->getRows("select id from esi_fleet 
                                                     where active > 0 
                                                     and (lastupdate < ? or lastupdate is null)"
                                        , [date("Y-m-d H:i:s", strtotime("now")-$crestTimer)]))
            {
                \AppRoot::doCliOutput(count($results)." fleets found");
                foreach ($results as $result) {
                    \AppRoot::doCliOutput("fleet: ".$result["id"]);
                    \MySQL::getDB()->doQuery("update esi_fleet set lastupdate = '".date("Y-m-d H:i:s")."' where id = ".$result["id"]);
                    \AppRoot::runCron(["esi", "fleet", "fleet", $result["id"]]);
                }
            }

            \AppRoot::doCliOutput("Running for ".\AppRoot::getExecTime()." seconds");
            sleep(1);
        }
        \AppRoot::doCliOutput("Finished run!");
    }

    function doFleet($arguments=[])
    {
        $api = new \esi\Api();
        while (count($arguments) > 0) {
            $fleet = \fleets\model\Fleet::findById(array_shift($arguments));
            if ($fleet)
                $this->getFleetMembers($fleet, $api);
        }
        $api->closeCurl();
    }

    function getFleetMembers(\fleets\model\Fleet $fleet, \esi\Api $api=null)
    {
        \AppRoot::doCliOutput("getFleetMembers($fleet->id)");
        if (!$fleet->id) {
            $fleet->active = false;
            $fleet->statusMessage = "Cannot call CREST, no fleet ID.";
            $fleet->store();
            return $fleet;
        }

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

        if (!$api)
            $api = new \esi\Api();

        $api->setToken($fleet->getBoss()->getToken());
        $api->get("v1/fleets/".$fleet->id."/members/");

        if ($api->success())
        {
            \AppRoot::debug("FLEET MEMBERS:");
            \AppRoot::debug($api->getResult());
            $locationTracker = new \map\controller\LocationTracker();

            if ($api->getResult())
            {
                // Dubbele execution voorkomen
                foreach ($api->getResult() as $fleetMember) {
                    $fleetMembers[] = [
                        (int)$fleet->id,
                        (int)$fleetMember->character_id,
                        (int)$fleetMember->wing_id,
                        (int)$fleetMember->squad_id,
                        (int)$fleetMember->solar_system_id,
                        (int)$fleetMember->ship_type_id,
                        (int)($fleetMember->takes_fleet_warp)?1:0
                    ];
                }
                // Reset fleet members
                \MySQL::getDB()->doQuery("delete from esi_fleet_member where fleetid = ?", [$fleet->id]);
                if (count($fleetMembers) > 0) {
                    $memberQuery = [];
                    foreach ($fleetMembers as $member) {
                        $memberQuery[] = "(".implode(", ", $member).")";
                    }
                    \MySQL::getDB()->doQuery("insert into esi_fleet_member (fleetid, characterid, wingid, squadid, solarsystemid, shiptypeid, takewarp) values ".implode(", ", $memberQuery));
                }

                foreach ($api->getResult() as $fleetMember)
                {
                    // Check character exists
                    /** @var \eve\model\Character $character */
                    $character = \esi\model\Character::findByID($fleetMember->character_id);
                    if (!$character) {
                        $controller = new \eve\controller\Character();
                        $controller->importCharacter($fleetMember->character_id);
                        $character = new \esi\model\Character((int)$fleetMember->character_id);
                    }
                    \AppRoot::doCliOutput(" - ".$character->name);
                    if (!$userSession)
                        \User::setUSER($character->getUser());

                    // Location tracker
                    $locationTracker->setCharacterLocation($character, (int)$fleetMember->solar_system_id, (int)$fleetMember->ship_type_id);
                    if (!$userSession)
                        \User::unsetUser();
                }

                $fleet->active = true;
                $fleet->statusMessage = "Tracking ".count($fleetMembers)." fleet members.";
                $fleet->store();
            } else {
                \AppRoot::doCliOutput("Could not parse result received from ESI. HELP....", "red");
                $fleet->active = false;
                $fleet->statusMessage = "Could not parse result received from ESI. HELP....";
                $fleet->store();
            }
        } else {
            if ($api->httpStatus >= 500 and $api->httpStatus < 600) {
                \AppRoot::doCliOutput("CREST call failed, Is ESI down??  Retrying in 5 minutes.", "red");
                $fleet->active = false;
                $fleet->statusMessage = "CREST call failed, Is ESI down??  Retrying in 5 minutes.";
                $fleet->lastUpdate = date("Y-m-d H:i:s", strtotime("now")+(60*5));
                $fleet->store();
            } else {
                \AppRoot::doCliOutput("Failed to call ESI for fleet info. ".$api->httpStatus." returned!", "red");
                $fleet->active = false;
                $fleet->statusMessage = "Failed to call ESI for fleet info. ".$api->httpStatus." returned!";
                $fleet->store();
            }
        }

        unset($api);
        return $fleet;
    }

    /**
     * get fleet
     * @param $url
     * @param $characterID
     * @return \esi\model\Fleet
     * @throws \Exception
     */
    function getFleetByURL($url, $characterID)
    {
        // ESI link?
        // https://esi.tech.ccp.is/v1/fleets/1082711409055/?datasource=tranquility
        $fleetID = null;
        if (strpos($url, "esi.tech.ccp") !== false) {
            $parts = explode("/", $url);
            while (count($parts) > 0) {
                $part = array_shift($parts);
                if ($part == "fleets") {
                    $fleetID = array_shift($parts);
                    break;
                }
            }
        }
        if (!$fleetID)
            throw new \Exception("Could not parse fleet link");

        $character = new \esi\model\Character($characterID);
        if (!$character->getToken())
            throw new \Exception($character->name." does not have a valid token");

        $api = new \esi\Api();
        $api->setToken($character->getToken());
        $api->get("v1/fleets/".$fleetID."/");
        if (!$api->success())
            throw new \Exception("Failed getting fleet information [http ".$api->httpStatus."]");

        /** @var \esi\model\Fleet $fleet */
        $fleet = \esi\model\Fleet::findOne(["id" => $fleetID]);
        if (!$fleet)
            $fleet = new \esi\model\Fleet();

        $fleet->id = $fleetID;
        $fleet->url = $url;
        $fleet->bossID = $characterID;
        $fleet->authGroupID = \User::getUSER()->getCurrentAuthGroupID();
        $fleet->active = 1;
        $fleet->store();
        return $fleet;
    }
}
