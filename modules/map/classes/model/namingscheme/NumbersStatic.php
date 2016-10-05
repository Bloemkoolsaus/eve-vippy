<?php
namespace map\model\namingscheme;

class NumbersStatic extends \map\model\NamingScheme
{
    function getNewWormholeName(\map\model\Wormhole $wormhole, $ignoreReservations=false)
    {
        $letters = "abcdefghijklmnopqrstuvwxyz";

        $classname = strtolower($wormhole->getSolarsystem()->getClass(true));
        $connectedSystems = $wormhole->getConnectedSystems();

        // Wat is het vorige wormhole?
        if (count($connectedSystems) > 0)
        {
            // We gaan er even vanuit dat het vorige systeem eerder toegevoegd is dan al de rest
            $previousWormhole = array_shift($connectedSystems);
            $previousSystem = $previousWormhole->getSolarsystem();

            $startingName = $previousWormhole->name;
            \AppRoot::debug("Startingsystem: ".$startingName);
            if ($wormhole->getChain()->getHomeSystem()->id == $previousSystem->id)
                $startingName = "";


            $wspaceStatics = [];
            $kspaceStatics = [];

            if ($wormhole->getSolarsystem()->isWSpace())
            {
                // Ben ik de static?
                foreach ($previousSystem->getStatics(false,false) as $static) {
                    if ($static["wspace"])
                        $wspaceStatics[] = strtolower($static["tag"]);
                    else
                        $kspaceStatics[] = strtolower($static["tag"]);
                }

                // Tellen na statics, tenzij het een van de statics is.
                if (count($wspaceStatics) > 0) {
                    $index = count($wspaceStatics) + 1;
                    for ($s = 0; $s < count($wspaceStatics); $s++) {
                        if ($wspaceStatics[$s] == $classname)
                            $index = $s + 1;
                    }
                } else
                    $index = 2;
            }
            else
            {
                $startingName .= ucfirst($classname[0]);
                $index = 0;
            }

            do
            {
                if ($wormhole->getSolarsystem()->isWSpace())
                {
                    // Controleer naam!
                    if ($index <= count($wspaceStatics))
                    {
                        // Dit is een van de statics
                        $statics = [];
                        foreach ($previousSystem->getStatics(false,false) as $static) {
                            $statics[] = $static;
                        }

                        // Welke static is dit?
                        if (isset($statics[$index-1]))
                        {
                            $static = $statics[$index - 1];

                            // kijk of de static signature bekend is.
                            $signature = null;
                            foreach (\scanning\model\Signature::getSignaturesBySolarSystem($previousSystem->id) as $sig)
                            {
                                if (strtolower($sig->sigType) == "wh") {
                                    if ($sig->sigTypeID == $static["id"]) {
                                        $signature = $sig;
                                        break;
                                    }
                                }
                            }

                            // Check of de static niet per ongeluk de weg terug is.
                            if ($signature !== null)
                            {
                                \AppRoot::debug("SIGNATURE: ".$signature);
                                $sigWhName = explode(" ",$signature->sigInfo);
                                $sigWhName = explode("-",$sigWhName[0]);
                                if ($sigWhName[1] != $startingName.$index)
                                {
                                    // Niet goed, geen static. Zet juiste naam.
                                    $index = count($wspaceStatics)+1;
                                }
                            }
                        }
                    }

                    $title = $startingName.$index;
                }
                else
                    $title = $startingName.$letters[$index];


                $searches = array("11111111","1111111","111111","11111","1111");
                $replacements = array("8x1","7x1","6x1","5x1","4x1");
                $title = str_replace($searches, $replacements, $title);

                $searches = array("22222222","2222222","222222","22222","2222");
                $replacements = array("8x2","7x2","6x2","5x2","4x2");
                $title = str_replace($searches, $replacements, $title);

                $searches = array("33333333","3333333","333333","33333","3333");
                $replacements = array("8x3","7x3","6x3","5x3","4x3");
                $title = str_replace($searches, $replacements, $title);

                $exists = false;
                foreach ($wormhole->getChain()->getWormholes() as $hole)
                {
                    if ($hole->isReservation() && $ignoreReservations)
                        continue;

                    if ($hole->name == $title) {
                        $exists = true;
                        break;
                    }
                }

                if ($exists)
                    $index++;
            }
            while ($exists);

            return $title;
        }

        return null;
    }

    function getWHTypeBySignatureName(\scanning\model\Signature $signature)
    {
        if (strlen(trim($signature->sigInfo)) == 0)
            return 0;

        $parts = explode(" ",$signature->sigInfo);
        $connectionName = $parts[0];
        $parts = explode("-", $connectionName);
        $wormholeName = array_pop($parts);
        $nextWhName = $wormholeName[strlen($wormholeName)-1];

        if (strlen($wormholeName) < strlen($signature->getWormhole()->name) || $nextWhName == 0)
        {
            // Dit is de weg terug! Kijk wat de andere kant is.
            if ($nextWhName == 0)
                $wormholeBack = \scanning\model\Wormhole::getWormholeBySystemID($signature->getChain()->homesystemID);
            else
                $wormholeBack = \scanning\model\Wormhole::getWormholeBySystemByName($wormholeName);

            if ($wormholeBack != null)
            {
                $lookForNames = [
                    $nextWhName."-".$signature->getWormhole()->name,
                    $wormholeBack->name."-".$signature->getWormhole()->name,
                    $signature->getWormhole()->name
                ];

                foreach (\scanning\model\Signature::getSignaturesBySolarSystem($wormholeBack->solarSystemID) as $sig)
                {
                    $sigWhName = explode(" ", $sig->sigInfo);
                    if (in_array($sigWhName[0], $lookForNames)) {
                        if ($sig->sigTypeID != 0 && $sig->sigTypeID != 9999)
                            return 9999;
                    }
                }
            }
        }

        if (is_numeric($nextWhName))
        {
            // Wspace system
            $i = 0;
            foreach ($signature->getSolarSystem()->getStatics(false, false) as $stype => $static)
            {
                if ($static["wspace"] > 0)
                    $i++;

                if ($i == $nextWhName)
                    return $static["id"];
            }
        }
        else
        {
            // Kspace system
            $nextWhType = $wormholeName[strlen($wormholeName)-2];
            foreach ($signature->getSolarSystem()->getStatics(false, false) as $stype => $static)
            {
                if (!$static["wspace"]) {
                    if (strtoupper($static["tag"][0]) == strtoupper($nextWhType)) {
                        if (strtolower($nextWhName) == "a")
                            return $static["id"];
                    }
                }
            }
        }

        return 0;
    }
}