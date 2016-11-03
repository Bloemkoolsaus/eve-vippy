<?php
namespace map\model\namingscheme;

class Letters extends \map\model\NamingScheme
{
    function getNewWormholeName(\map\model\Wormhole $wormhole, $ignoreReservations=false)
    {
        $startIndex = 0;
        $classname = strtolower($wormhole->getSolarsystem()->getClass(true));
        $letters = ["a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z"];

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
            if (isset($letters[$startIndex])) {
                $titleLetters = [$letters[$startIndex]];
            } else {
                $nrLetters = count($letters)-1;
                $titleLetters = [
                    $letters[($startIndex>0)?floor($startIndex/$nrLetters)-1:0],
                    $letters[($startIndex%$nrLetters)-1]
                ];
            }

            $title = $classname.implode("",$titleLetters);
            foreach ($wormhole->getChain()->getWormholes() as $hole) {
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