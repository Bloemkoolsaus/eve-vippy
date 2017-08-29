<?php
namespace scanning\controller;

class Signature
{
    /**
     * Check open signatures. Markeer volledig gescand indien geen openstaande sigs.
     * @param \eve\model\SolarSystem $solarSystem
     * @param \map\model\Map|null $map
     */
    function checkOpenSignatures($solarSystem, $map=null)
    {
        \AppRoot::debug("checkOpenSignatures($solarSystem->name)");
        $openSignatures = array();
        foreach (\scanning\model\Signature::getSignaturesBySolarSystem($solarSystem->id) as $signature)
        {
            if ($signature->sigType == null || strlen(trim($signature->sigType)) == 0)
                $openSignatures[] = $signature;
        }
        \AppRoot::debug(count($openSignatures)." open signatures");
        if (count($openSignatures) == 0)
        {
            // Geen open sigs. Markeer volledig gescand.
            $wormhole = \scanning\model\Wormhole::getWormholeBySystemID($solarSystem->id, ($map)?$map->id:\User::getSelectedChain());
            if ($wormhole != null)
                $wormhole->markFullyScanned();
        }
    }
}