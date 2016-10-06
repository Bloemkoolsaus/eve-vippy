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
         * Check wh-nummber, connection bijwerken.
         */
        \AppRoot::debug("Signature->whTypeID: ".$signature->whTypeID);
        if ($signature->isWormhole())
        {
            // Parse signature name om de de juiste connectie te zoeken.
            $parts = explode(" ", $signature->sigInfo);
            $parts = explode("-", $parts[0]);
            $wormholename = (count($parts) > 1) ? $parts[1] : $parts[0];
            \AppRoot::doCliOutput("UPDATE Connection Type: ".$wormholename);

            // Zoek wormhole type.
            \AppRoot::debug("Find statics");
            $wspaceStatics = 0;
            foreach (\map\model\WormholeType::findStaticBySolarSystem($signature->solarSystemID) as $static)
            {
                $staticName = null;
                $wormhole = \map\model\Wormhole::findOne(["chainid" => $map->id, "solarsystemid" => $signature->solarSystemID]);
                if ($wormhole->isHomeSystem())
                    $wormhole->name = "0";

                if ($static->isHighsec())
                    $staticName = $wormhole->name."Ha";
                else if ($static->isLowsec())
                    $staticName = $wormhole->name."La";
                else if ($static->isNullsec())
                    $staticName = $wormhole->name."Na";
                else {
                    $wspaceStatics++;
                    $staticName = $wormhole->name.$wspaceStatics;
                }

                \AppRoot::debug("Staticname: ".$staticName);
                if ($staticName && $staticName == $wormholename) {
                    \AppRoot::doCliOutput("Store wormhole type");
                    $signature->whTypeID = $static->id;
                    $signature->store();
                }
            }

            if (!$signature->whTypeID) {
                $signature->whTypeID = 9999;
                $signature->store();
            }

            // Zoek dit wormhole
            foreach (\map\model\Wormhole::getWormholesByAuthgroup($signature->authGroupID) as $wormhole)
            {
                \AppRoot::debug("Wormhole: ".$wormhole->name);
                if (trim(strtolower($wormhole->name)) == trim(strtolower($wormholename)))
                {
                    \AppRoot::doCliOutput("Set connection type to wh: ".$wormholename);
                    $fromWormhole = \map\model\Wormhole::getWormholeBySystemID($signature->solarSystemID, $map->id);
                    $connection = \map\model\Connection::getConnectionByWormhole($fromWormhole->id, $wormhole->id, $map->id);
                    if ($connection != null) {
                        if ($connection->fromWormholeID == $wormhole->id) {
                            $connection->fromWHTypeID = 9999;
                            $connection->toWHTypeID = $signature->whTypeID;
                        } else {
                            $connection->toWHTypeID = 9999;
                            $connection->fromWHTypeID = $signature->whTypeID;
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
        foreach (\map\model\Signature::findAll(["solarsystemid" => $solarSystem->id, "authgroupid" => $map->authgroupID]) as $signature) {
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