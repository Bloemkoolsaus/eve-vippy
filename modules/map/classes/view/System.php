<?php
namespace map\view;

class System
{
    function getDetails($arguments=[])
    {
        $wormhole = new \map\model\Wormhole(array_shift($arguments));
        $system = $wormhole->getSolarsystem();

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("system", $system);
        $tpl->assign("wormhole", $wormhole);

        if ($system->isWSpace())
            $tpl->assign("whEffectsData", $this->getWHEffectsData($system));

        return $tpl->fetch("map/system/solarsystem");
    }

    function getRename($arguments=[])
    {
        $wormhole = new \map\model\Wormhole(array_shift($arguments));
        $system = $wormhole->getSolarsystem();

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("system", $system);
        $tpl->assign("wormhole", $wormhole);

        return $tpl->fetch("map/system/rename");
    }

    function getActivity($arguments=[])
    {
        $wormhole = new \map\model\Wormhole(array_shift($arguments));
        $system = $wormhole->getSolarsystem();
        $filename = $system->buildActivityGraph();
        $generated = \Tools::getAge($system->getActivityGraphAge());
        return json_encode(array("url" => $filename, "date" => "<b>Graph age:</b> &nbsp; ".strtolower($generated)));
    }

    function getTradehubs($arguments=[])
    {
        \AppRoot::doCliOutput("getTradehubs()");
        $wormhole = new \map\model\Wormhole(array_shift($arguments));
        $system = $wormhole->getSolarsystem();
        $closeSysConsole = new \map\console\ClosestSystems();
        $closestSystems = $closeSysConsole->getClosestSystems($system);
        \AppRoot::doCliOutput("/getTradehubs()");

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("systems", $closestSystems);
        return $tpl->fetch("map/system/tradehub");
    }

    function getContext($arguments=[])
    {
        $wormholeID = array_shift($arguments);
        $wormhole = new \map\model\Wormhole($wormholeID);

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("wormhole", $wormhole);

        if ($wormhole->getSolarsystem() && $wormhole->getSolarsystem()->isKSpace()) {
            $closeSysConsole = new \map\console\ClosestSystems();
            $closestSystems = $closeSysConsole->getClosestSystems($wormhole->getSolarsystem(), true);
            if (count($closestSystems) > 0)
                $tpl->assign("closestsystem", $closestSystems[0]);
        }

        return $tpl->fetch("map/system/contextmenu");
    }

    /**
     * Get wormhole effects data
     * @param \map\model\SolarSystem $system
     * @return string
     */
    private function getWhEffectsData($system)
    {
        $negativePositives = array("signatureRadiusMultiplier","heatDamageMultiplier");
        $typeNameReplacements = array("armorDamageAmountMultiplier"		=> "Armor Repair amount multiplier",
            "shieldBonusMultiplier"				=> "Shield Repair amount multiplier",
            "armorDamageAmountMultiplierRemote" => "Remote armor repair amount multiplier",
            "shieldBonusMultiplierRemote"		=> "Remote shield repair amount multiplier",
            "signatureRadiusMultiplier"			=> "Signature radius",
            "agilityMultiplier"			=> "Agility Modifier",
            "maxRangeMultiplier"		=> "Targeting Range",
            "fallofMultiplier"			=> "Turret Falloff Modifier",
            "droneRangeMultiplier"		=> "Drone Control Range",
            "aoeVelocityMultiplier"		=> "Missile Explosion Velocity multiplier",
            "overloadBonusMultiplier"	=> "Overload boost",
            "energyTransferAmountBonus"	=> "Energy Transfer amount multiplier");

        $names = array("bonus","multiplier","resistance","modifier");
        $nnames = array("","","resist","");

        $effectName = "";
        $positives = array();
        $negatives = array();

        if (!$sysEffect = $system->getEffect())
        {
            // Geen effect..!!
            return "";
        }

        if ($sysEffect == "Wolf-Rayet Star")
            $sysEffect = "Wolf Rayet";
        if ($sysEffect == "Pulsar")
            $negativePositives[] = "rechargeRateMultiplier";

        $sysClassNr = $system->getClass(true);
        $sysClassNr = str_replace("C","",$sysClassNr);

        $cacheFileName = "solarsystem/".$system->id."/wheffects.json";

        if ($cache = \Cache::file()->get($cacheFileName))
            $results = json_decode($cache, true);
        else
        {
            if ($results = \MySQL::getDB()->getRows("SELECT i.typename, t.attributename, t.displayname, a.valuefloat, t.unitid
                                                    FROM 	".\eve\Module::eveDB().".dgmtypeattributes a
                                                        INNER JOIN ".\eve\Module::eveDB().".dgmattributetypes t ON t.attributeid = a.attributeid
                                                        INNER JOIN ".\eve\Module::eveDB().".invtypes i ON i.typeid = a.typeid
                                                    WHERE 	i.typename LIKE '%".$sysEffect."%'
                                                    AND		i.typename LIKE '%Class ".$sysClassNr."%'"))
            {
                \Cache::file()->set($cacheFileName, json_encode($results));
            }
        }

        if ($results)
        {
            foreach ($results as $result)
            {
                $effectName = $result["typename"];
                $effectType = $result["attributename"];
                $effectTypeName = $result["displayname"];

                $amount = $result["valuefloat"];
                if ($result["unitid"] == 109 || $result["unitid"] == 104)
                    $amount = ($amount-1)*100;
                if ($result["unitid"] == 124 || $result["unitid"] == 105)
                    $amount = $amount*-1;

                foreach ($typeNameReplacements as $type => $name)
                {
                    if ($effectType == $type)
                        $effectTypeName = $name;
                }

                $effectTypeName = str_replace($names,$nnames,strtolower($effectTypeName));
                $effectTypeName = trim(ucwords($effectTypeName));

                $effect = array("name" => $effectTypeName, "amount" => $amount);

                if (in_array($effectType, $negativePositives))
                {
                    if ($amount >= 0)
                        $negatives[] = $effect;
                    else
                        $positives[] = $effect;
                }
                else
                {
                    if ($amount >= 0)
                        $positives[] = $effect;
                    else
                        $negatives[] = $effect;
                }
            }
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("effectname", $effectName);
        $tpl->assign("positives", $positives);
        $tpl->assign("negatives", $negatives);
        return $tpl->fetch("scanning/wheffectsdata");
    }
}