<?php
namespace crest\console;

class Fleet
{
    function doFleet($arguments=[])
    {
        \AppRoot::doCliOutput("doFleet(".implode(",",$arguments).")");
        /** @var \crest\model\Fleet $fleet */
        $fleet = \crest\model\Fleet::findById(array_shift($arguments));
        if (!$fleet)
            return null;

        $character = new \crest\model\Character($fleet->bossID);

        $crest = new \crest\Api();
        $crest->setToken($character->getToken());
        $crest->get("fleets/".$fleet->id."/members/");

        if ($crest->success())
        {
            foreach ($crest->getResult()->items as $fleetMember)
            {
                \MySQL::getDB()->updateinsert("mapwormholecharacterlocations", [
                    "characterid" => $fleetMember->character->id,
                    "solarsystemid" => $fleetMember->solarSystem->id,
                    "shiptypeid" => $fleetMember->ship->id,
                    "authgroupid" => $fleet->authGroupID,
                    "lastdate" => date("Y-m-d H:i:s")
                ],[
                    "characterid" => $fleetMember->character->id
                ]);
            }
        }
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