<?php
namespace map\model\namingscheme;

class Letters extends \map\model\NamingScheme
{
    function getNewWormholeName(\map\model\Wormhole $wormhole, $ignoreReservations=false)
    {
        $letters = "abcdefghijklmnopqrstuvwxyz";

        $startIndex = 0;
        $classname = strtolower($wormhole->getSolarsystem()->getClass(true));

        // Ben ik verbonden met homesystem?
        if ($wormhole->isConnectedTo($wormhole->getChain()->homesystemID))
        {
            // Ben ik de static?
            $statics = array();
            foreach ($wormhole->getChain()->getHomeSystem()->getStatics(false,false) as $static) {
                $statics[] = strtolower($static["tag"]);
            }

            if (in_array($classname, $statics))
            {
                // Ik zou de static kunnen zijn.
                $title = $classname."s";

                // Bestaat deze naam al?
                $exists = false;
                foreach ($wormhole->getChain()->getWormholes() as $hole)
                {
                    if ($hole->isReservation() && $ignoreReservations)
                        continue;
                    if (strtolower($hole->name) == $title)
                        $exists = true;
                }

                if (!$exists)
                    return $title;
            }

            // Ik ben niet de static. Maar wel verbonden met home, dus xyz.
            $startIndex = 23;
        }

        $exists = false;
        $title = $classname;
        do
        {
            $exists = false;
            $title = $classname.$letters[$startIndex];
            foreach ($wormhole->getChain()->getWormholes() as $hole)
            {
                if ($hole->isReservation() && $ignoreReservations)
                    continue;
                if (strtolower($hole->name) == $title) {
                    $exists = true;
                    break;
                }
            }

            if ($exists)
                $startIndex++;
        }
        while ($exists);

        return $title;
    }
}