<?php
namespace map\view\map;

class Signatures
{
    function getOverview(\map\model\Map $map, $arguments=[])
    {
        \AppRoot::debug("----- getSignatures() -----");
        $signatures = [];
        $solarSystem = \map\model\SolarSystem::getSolarsystemByName(\Tools::REQUEST("system"));

        if ($map && $solarSystem)
        {
            $currentDate = date("Y-m-d H:i:s");
            $checkCache = true;
            while (count($arguments) > 0) {
                $arg = array_shift($arguments);
                if ($arg == "nocache")
                    $checkCache = false;
            }

            // Kijk of er iets veranderd is in de chain sinds de laatste check. Zo niet, is natuurlijk geen update nodig.
            if ($checkCache)
            {
                // Bestaat er wel een cache?
                iF (isset($_SESSION["vippy"]["map"]["cache"]["signatures"][$solarSystem->id]))
                {
                    $cacheDate = $_SESSION["vippy"]["map"]["cache"]["signatures"][$solarSystem->id];
                    if ($result = \MySQL::getDB()->getRow("select max(updatedate) as lastdate
                                                           from   map_signature
                                                           where  authgroupid = ?
                                                           and    solarsystemid = ?"
                                                , [$map->authgroupID, $solarSystem->id]))
                    {
                        \AppRoot::debug("cache-date: " . date("Y-m-d H:i:s", strtotime($cacheDate)));
                        \AppRoot::debug("lastupdate: " . date("Y-m-d H:i:s", strtotime($result["lastdate"])));

                        if (strtotime($cacheDate) > strtotime($result["lastdate"])) {
                            if (strtotime($cacheDate) > mktime(date("H"), date("i") - 1, date("s"), date("m"), date("d"), date("Y"))) {
                                \AppRoot::debug("do cache");
                                return "cached";
                            }
                        }
                    }
                }
            }

            if ($results = \MySQL::getDB()->getRows("select s.*, if (t.name in ('pos','citadel'), 0, 1) as sorting
                                                    from    map_signature s
                                                        left join map_signature_type t on t.id = s.sigtypeid
                                                    where   s.deleted = 0
                                                    and     s.solarsystemid = ?
                                                    and     s.authgroupid = ?
                                                    order by sorting, sigid, siginfo"
                                        , [$solarSystem->id, $map->authgroupID]))
            {
                foreach ($results as $result)
                {
                    $sig = new \map\model\Signature();
                    $sig->load($result);

                    $sigData = [
                        "id" => $sig->id,
                        "sigid" => $sig->sigID,
                        "type" => ($sig->getSignatureType())?$sig->getSignatureType()->name:"",
                        "info" => $sig->sigInfo,
                        "wormhole" => null,
                        "scanage" => \Tools::getAge($sig->scanDate),
                        "scanuser" => $sig->getScannedByUser()->getFullName(),
                        "updateage" => \Tools::getAge($sig->updateDate),
                        "updateuser" => $sig->getUpdatedByUser()->getFullName()
                    ];

                    if ($sig->isWormhole()) {
                        $sigData["wormhole"] = [
                            "type" => $sig->getWormholeType()->name,
                            "desto" => $sig->getWormholeType()->getDestinationclass()->tag
                        ];
                    }
                    $signatures[] = $sigData;
                }
            }

            $_SESSION["vippy"]["map"]["cache"]["signatures"][$solarSystem->id] = date("Y-m-d H:i:s");
        }

        return json_encode($signatures);
    }

    function getStore(\map\model\Map $map, $arguments=[])
    {
        \AppRoot::debug("storeSignature(".\Tools::REQUEST("system").")");

        $solarSystem = \map\model\SolarSystem::getSolarsystemByName(\Tools::REQUEST("system"));
        if (!$map || !$solarSystem)
            return false;

        $signature = null;
        if (\Tools::REQUEST("id"))
            $signature = \map\model\Signature::findById(\Tools::REQUEST("id"));
        if (!$signature) {
            $signature = \map\model\Signature::findOne([
                "sigid" => \Tools::REQUEST("sigid"),
                "solarsystemid" => $solarSystem->id,
                "authgroupid" => $map->authgroupID
            ]);
        }
        if (!$signature)
            $signature = new \map\model\Signature();

        $signature->solarSystemID = $solarSystem->id;
        $signature->authGroupID = $map->authgroupID;
        $signature->sigID = \Tools::REQUEST("sigid");
        $signature->sigInfo = \Tools::REQUEST("info");

        $signature->sigTypeID = null;
        $sigType = \map\model\SignatureType::findOne(["name" => \Tools::REQUEST("type")]);
        if ($sigType)
            $signature->sigTypeID = $sigType->id;

        $signature->whTypeID = null;
        $whtype = \map\model\WormholeType::findByName(\Tools::REQUEST("whtype"));
        if ($whtype)
            $signature->whTypeID = $whtype->id;

        $controller = new \map\controller\Signature();
        $controller->storeSignature($map, $signature);
        return "stored";
    }

    function getDelete(\map\model\Map $map, $arguments=[])
    {
        $sigid = array_shift($arguments);
        if ($sigid == "all")
        {
            $solarSystem = \map\model\SolarSystem::getSolarsystemByName(\Tools::REQUEST("system"));
            if ($solarSystem) {
                foreach (\map\model\Signature::findAll(["solarsystemid" => $solarSystem->id]) as $signature) {
                    if (!$signature->getSignatureType() || $signature->getSignatureType()->mayCleanup())
                        $signature->delete();
                }
                return "deleted";
            }
        }
        else
        {
            /** @var \map\model\Signature $signature */
            $signature = \map\model\Signature::findById($sigid);
            if ($signature) {
                $signature->delete();
                return "deleted";
            }
        }

        return "signature not found";
    }

    function getCopypaste(\map\model\Map $map, $arguments=[], $mapID=null, $systemID=null)
    {
        $solarSystem = \map\model\SolarSystem::getSolarsystemByName((\Tools::REQUEST("system"))?:$systemID);
        if (!$map || !$solarSystem)
            return false;

        $controller = new \map\controller\Signature();
        $nrSignatures = 0;
        foreach (explode("\n", \Tools::POST("signatures")) as $line)
        {
            $parts = explode("\t", $line);

            $sigID = strtoupper($parts[0][0].$parts[0][1].$parts[0][2]);
            $sigName = strtolower(trim($parts[3]));

            if (strlen(trim($sigID)) == 0)
                continue;

            if (strpos(strtolower($line),"cosmic signature") !== false)
            {
                $signature = \map\model\Signature::findOne([
                    "solarsystemid" => $solarSystem->id,
                    "authgroupid" => $map->authgroupID,
                    "sigid" => $sigID
                ]);
                if (!$signature)
                    $signature = new \map\model\Signature();

                $signature->sigID = $sigID;
                $signature->solarSystemID = $solarSystem->id;
                $signature->authGroupID = $map->authgroupID;

                $sigTypePasted = strtolower(trim(str_replace("site","",strtolower($parts[2]))));
                if (strlen(trim($sigTypePasted)) > 0) {
                    $sigTypePasted = $sigTypePasted == "wormhole"? "WH": $sigTypePasted;
                    $signatureType = \map\model\SignatureType::findOne(["name" => $sigTypePasted]);
                    if ($signatureType)
                        $signature->sigTypeID = $signatureType->id;
                }

                $signature->sigInfo = (trim($signature->sigInfo) != "") ? $signature->sigInfo : $sigName;
                $signature->signalStrength = str_replace("%","",$parts[4]);
                $signature->deleted = false;
                $controller->storeSignature($map, $signature);

                $nrSignatures++;
            }
            else
            {
                // Anomaly
                $sigID = strtoupper($parts[0][0].$parts[0][1].$parts[0][2]);
                $sigType = $parts[1];

                // Check type
                $anomType = \map\model\AnomalyType::findOne(["name" => $sigName]);
                if (!$anomType) {
                    $anomType = new \map\model\AnomalyType();
                    $anomType->name = $sigName;
                    $anomType->type = $sigType;
                    $anomType->store();
                }

                // Check eerst of de sig al bestaat.
                $anomaly = \map\model\Anomaly::findOne(["signatureid" => $sigID, "solarsystemid" => $solarSystem->id, "authgroupid" => $map->authgroupID]);
                if (!$anomaly) {
                    $anomaly = new \map\model\Anomaly();
                    $anomaly->authGroupID = $map->authgroupID;
                    $anomaly->solarSystemID = $solarSystem->id;
                }

                $anomaly->typeID = $anomType->id;
                $anomaly->signatureID = $sigID;
                $anomaly->description = $sigName;
                $anomaly->store();
            }
        }

        if ($nrSignatures > 4)
        {
            // Remove old signatures
            foreach (\map\model\Signature::findAll(["solarsystemid" => $solarSystem->id,
                                                    "authgroupid" => $map->getAuthGroup()->id,
                                                    "deleted" => 0]) as $sig)
            {
                if (strtotime($sig->updateDate) < strtotime("now")-3600)
                {
                    if ($sig->getSignatureType()) {
                        if ($sig->getSignatureType()->mayCleanup())
                            $sig->delete();
                    } else
                        $sig->delete();
                }
            }
        }

        return "stored ".$nrSignatures." signatures";
    }
}