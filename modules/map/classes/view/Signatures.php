<?php
namespace map\view;

class Signatures
{
    function getOverview($arguments=[])
    {
        \AppRoot::debug("----- getSignatures() -----");
        $signatures = [];

        $map = \map\model\Map::findByName(\Tools::REQUEST("map"));
        $solarSystem = \map\model\System::getSolarsystemByName(\Tools::REQUEST("system"));

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
                    if ($result = \MySQL::getDB()->getRow("	SELECT	MAX(s.updatedate) AS lastdate
                                                        FROM	mapsignatures s
                                                            INNER JOIN mapwormholechains c ON c.authgroupid = s.authgroupid
                                                        WHERE	c.id = ?"
                                                , [$map->id]))
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

            foreach (\map\model\Signature::findAll(["deleted" => 0, "solarsystemid" => $solarSystem->id, "authgroupid" => $map->authgroupID]) as $sig)
            {
                $sigData = [
                    "id" => $sig->id,
                    "sigid" => $sig->sigID,
                    "type" => $sig->sigType,
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

            $_SESSION["vippy"]["map"]["cache"]["signatures"][$solarSystem->id] = date("Y-m-d H:i:s");
        }

        return json_encode($signatures);
    }

    function getStore($arguments=[])
    {
        \AppRoot::debug("storeSignature(".\Tools::REQUEST("system").")");

        $map = \map\model\Map::findByName(\Tools::REQUEST("map"));
        $solarSystem = \map\model\System::getSolarsystemByName(\Tools::REQUEST("system"));
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

        $signature->sigID = \Tools::REQUEST("sigid");
        $signature->sigType = \Tools::REQUEST("type");
        $signature->sigInfo = \Tools::REQUEST("info");
        $signature->solarSystemID = $solarSystem->id;
        $signature->authGroupID = $map->authgroupID;

        $signature->typeID = 0;
        $whtype = \map\model\WormholeType::findByName(\Tools::REQUEST("whtype"));
        if ($whtype)
            $signature->typeID = $whtype->id;

        $controller = new \map\controller\Signature();
        $controller->storeSignature($map, $signature);
        return "stored";
    }

    function getDelete($arguments=[])
    {
        $sigid = array_shift($arguments);
        if ($sigid == "all")
        {
            $solarSystem = \map\model\System::getSolarsystemByName(\Tools::REQUEST("system"));
            if ($solarSystem) {
                foreach (\map\model\Signature::findAll(["solarsystemid" => $solarSystem->id]) as $signature) {
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

    function getCopypaste($arguments=[])
    {
        $map = \map\model\Map::findByName(\Tools::REQUEST("map"));
        $solarSystem = \map\model\System::getSolarsystemByName(\Tools::REQUEST("system"));
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
                $signature = \map\model\Signature::findOne(["solarsystemid" => $solarSystem->id, "sigid" => $sigID]);
                if (!$signature)
                    $signature = new \map\model\Signature();

                $signature->sigID = $sigID;
                $signature->solarSystemID = $solarSystem->id;
                $signature->authGroupID = $map->authgroupID;

                $sigTypePasted = strtolower(trim(str_replace("site","",strtolower($parts[2]))));
                if (strlen(trim($sigTypePasted)) > 0) {
                    $sigTypePasted = $sigTypePasted == "wormhole"? "WH": $sigTypePasted;
                    $signature->sigType = $sigTypePasted;
                }
                $signature->sigInfo = (trim($signature->sigInfo) != "") ? $signature->sigInfo : $sigName;
                $signature->signalStrength = str_replace("%","",$parts[4]);
                $signature->deleted = false;
                $signature->store();
                $controller->storeSignature($map, $signature);

                $nrSignatures++;
            }
            else
            {
                // Anomaly
                $sigID = strtoupper($parts[0][0].$parts[0][1].$parts[0][2]);
                $sigType = $parts[1];

                // Check eerst of de sig al bestaat.
                if ($existID = \scanning\Anomaly::checkSignatureID($sigID))
                    $anomaly = new \scanning\Anomaly($existID);
                else
                    $anomaly = new \scanning\Anomaly();

                // Check of de anomaly type al bestaat
                if (!$anomID = \scanning\AnomalyType::getAnomalyIdByName($sigName))
                {
                    $anom = new \scanning\AnomalyType();
                    $anom->name = $sigName;
                    $anom->type = $sigType;
                    $anom->store();
                    $anomID = $anom->id;
                }

                $anomaly->solarSystemID = $solarSystem->id;
                $anomaly->chainID = $map->id;
                $anomaly->anomalyID = $anomID;
                $anomaly->signatureID = $sigID;
                $anomaly->store();
            }
        }

        if ($nrSignatures > 4)
        {
            // Remove old signatures
            foreach (\map\model\Signature::findAll(["solarsystemid" => $solarSystem->id, "authgroupid" => $map->getAuthGroup()->id]) as $sig)
            {
                if (strtotime($sig->updateDate) < strtotime("now")-3600) {
                    if (strtoupper($sig->sigType) !== "POS") {
                        $sig->delete();
                    }
                }
            }
        }

        return "stored ".$nrSignatures." signatures";
    }
}