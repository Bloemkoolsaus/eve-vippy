<?php
namespace map\controller;

class Signature
{
    function storeSignature(\map\model\Map $map, \map\model\Signature $signature)
    {
        // Tellen in statistieken?
        $countInStats = false;
        if ($signature->id) {
            // Gewijzigd?
            $oldSignature = new \map\model\Signature($signature->id);
            if ($signature->sigTypeID != $oldSignature->sigTypeID) {
                if ($signature->getSignatureType())
                    $countInStats = true;
            }
        } else {
            // Nieuwe signature
            if ($signature->getSignatureType())
                $countInStats = true;
        }

        $signature->store();


        /**
         * Toevoegen in statistsieken
         */
        if ($countInStats)
        {
            $stat = new \stats\model\Signature();
            $stat->userID = \User::getUSER()->id;
            $stat->corporationID = \User::getUSER()->getMainCharacter()->getCorporation()->id;
            $stat->signatureID = $signature->id;
            $stat->chainID = $map->id;
            $stat->scandate = date("Y-m-d H:i:s");
            $stat->store();
        }


        /**
         * Systeem toevoegen
         */
        if ($map && $map->getSetting("create-unmapped"))
        {
            \AppRoot::doCliOutput("add unmapped system");
            if ($signature->isWormhole() && !$signature->deleted) {
                $controller = new \map\controller\Wormhole();
                $controller->addWormholeBySignature($map, $signature);
            }
        }
        
        /**
         * Check open signaturs
         */
        $this->checkOpenSignatures($signature->getSolarSystem(), $map);
    }

    /**
     * Check open signatures.
     *  Markeer volledig gescand indien geen openstaande sigs.
     * @param \map\model\SolarSystem $solarSystem
     * @param \map\model\Map $map
     */
    function checkOpenSignatures(\map\model\SolarSystem $solarSystem, \map\model\Map $map)
    {
        \AppRoot::debug("checkOpenSignatures($solarSystem->name)");

        $openSignatures = array();
        foreach (\map\model\Signature::findAll(["solarsystemid" => $solarSystem->id, "authgroupid" => $map->authgroupID]) as $signature) {
            if ($signature->deleted)
                continue;
            if (!$signature->getSignatureType())
                $openSignatures[] = $signature;
        }

        \AppRoot::debug(count($openSignatures)." open signatures");
        if (count($openSignatures) == 0) {
            // Geen open sigs. Markeer volledig gescand.
            $wormhole = \scanning\model\Wormhole::getWormholeBySystemID($solarSystem->id, $map->id);
            if ($wormhole != null)
                $wormhole->markFullyScanned();
        }
    }
}