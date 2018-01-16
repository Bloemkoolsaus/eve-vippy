<?php
namespace eve\controller;

class Corporation
{
    /**
     * Import Corporation
     * @param $corporationID
     * @return \eve\model\Corporation|null
     */
    function importCorporation($corporationID)
    {
        $corporation = new \eve\model\Corporation($corporationID);
        \AppRoot::doCliOutput("Import Corporation ".$corporation->name);

        // Alleen udpaten indien ouder dan een uur
        if (strtotime($corporation->updateDate) <= strtotime("now")-3600)
        {
            $esi = new \esi\Api();
            $esi->get("v4/corporations/".$corporation->id."/");
            if ($esi->success()) {
                $corporation->name = $esi->getResult()->name;
                $corporation->ticker = $esi->getResult()->ticker;
                $corporation->allianceID = (isset($esi->getResult()->alliance_id))?$esi->getResult()->alliance_id:null;
                $corporation->ceoID = $esi->getResult()->ceo_id;
                $corporation->store();

                if (isset($esi->getResult()->alliance_id)) {
                    $alliance = \eve\model\Alliance::findById($esi->getResult()->alliance_id);
                    if (!$alliance) {
                        $esiAlliance = new \esi\Api();
                        $esiAlliance->get("v3/alliances/".$corporation->allianceID."/");
                        if ($esiAlliance->success()) {
                            $alliance = new \eve\model\Alliance();
                            $alliance->id = $corporation->allianceID;
                            $alliance->name = $esiAlliance->getResult()->name;
                            $alliance->ticker = $esiAlliance->getResult()->ticker;
                            $alliance->store();
                        }
                    }
                }
            }
        }

        return $corporation;
    }


    /**
     * @param $corporationID
     * @return bool
     * @deprecated
     */
    function corporationExists($corporationID)
    {
        $corp = \eve\model\Corporation::findByID($corporationID);
        return ($corp)?true:false;
    }
}