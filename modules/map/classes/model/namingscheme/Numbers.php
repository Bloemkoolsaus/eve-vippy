<?php
namespace map\model\namingscheme;

class Numbers extends \map\model\NamingScheme
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


            if ($wormhole->getSolarsystem()->isWSpace())
            {
                // Ben ik de static?
                $wspaceStatics = [];
                $kspaceStatics = [];
                foreach ($previousSystem->getStatics(false,false) as $static) {
                    if ($static["wspace"])
                        $wspaceStatics[] = strtolower($static["tag"]);
                    else
                        $kspaceStatics[] = strtolower($static["tag"]);
                }

                // Tellen na statics, tenzij het een van de statics is.
                $index = count($wspaceStatics)+1;
                if ($index <= 2)
                    $index = 2;
                for ($s=0; $s<count($wspaceStatics); $s++) {
                    if ($wspaceStatics[$s] == $classname)
                        $index = 1;
                }
            }
            else
            {
                $startingName .= ucfirst($classname[0]);
                $index = 0;
            }

            do
            {
                $title = $startingName.(($wormhole->getSolarsystem()->isWSpace()))?$index:$letters[$index];
                $title = $this->parseTitle($title);

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

    function parseTitle($title)
    {
        $searches = array("11111111","1111111","111111","11111","1111");
        $replacements = array("8x1","7x1","6x1","5x1","4x1");
        $title = str_replace($searches, $replacements, $title);

        $searches = array("22222222","2222222","222222","22222","2222");
        $replacements = array("8x2","7x2","6x2","5x2","4x2");
        $title = str_replace($searches, $replacements, $title);

        $searches = array("33333333","3333333","333333","33333","3333");
        $replacements = array("8x3","7x3","6x3","5x3","4x3");
        $title = str_replace($searches, $replacements, $title);

        return $title;
    }
}