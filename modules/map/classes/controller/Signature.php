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
            if (trim(strtoupper($signature->sigType)) != trim(strtoupper($oldSignature->sigType))) {
                if (strlen(trim($signature->sigType)) > 0)
                    $countInStats = true;
            }
        } else {
            // Nieuwe signature
            if (strlen(trim($signature->sigType)) > 0)
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
            \AppRoot::debug("add unmapped system");
            if (strtolower(trim($signature->sigType)) == "wh" && !$signature->deleted) {
                $controller = new \map\controller\Wormhole();
                $controller->addWormholeBySignature($map, $signature);
            }
        }


        /**
         * Check wh-nummber, connection bijwerken.
         */
        if ($signature->typeID > 0 && $signature->typeID != 9999)
        {
            // Parse signature name om de de juiste connectie te zoeken.
            $parts = explode(" ", $signature->sigInfo);
            $parts = explode("-", $parts[0]);
            $wormholename = (count($parts) > 1) ? $parts[1] : $parts[0];
            \AppRoot::debug("UPDATE Connection Type: ".$wormholename);

            // Zoek dit wormhole
            foreach (\map\model\Wormhole::getWormholesByAuthgroup($signature->authGroupID) as $wormhole) {
                if (trim(strtolower($wormhole->name)) == trim(strtolower($wormholename))) {
                    $fromWormhole = \map\model\Wormhole::getWormholeBySystemID($signature->solarSystemID, $map->id);
                    $connection = \map\model\Connection::getConnectionByWormhole($fromWormhole->id, $wormhole->id, $map->id);
                    if ($connection != null) {
                        if ($connection->fromWormholeID == $wormhole->id) {
                            $connection->fromWHTypeID = 9999;
                            $connection->toWHTypeID = $signature->typeID;
                        } else {
                            $connection->toWHTypeID = 9999;
                            $connection->fromWHTypeID = $signature->typeID;
                        }
                        $connection->store(false);
                    }
                }
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
        foreach (\scanning\model\Signature::getSignaturesBySolarSystem($solarSystem->id) as $signature) {
            if ($signature->sigType == null || strlen(trim($signature->sigType)) == 0)
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