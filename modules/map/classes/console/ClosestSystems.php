<?php
namespace map\console;

class ClosestSystems
{
    function getClosestSystems(\eve\model\SolarSystem $system, $showOnMapOnly=false)
    {
        $data = [];
        foreach (\map\model\ClosestSystem::getClosestSystemsBySystemID() as $sys)
        {
            if ($showOnMapOnly && !$sys->showOnMap)
                continue;

            $nrJumps = $system->getNrJumpsTo($sys->getSolarSystem()->id);
            $data[$nrJumps][] = $sys->getSolarSystem();
        }

        ksort($data);
        $solarSystems = [];
        foreach ($data as $jumps => $systems) {
            foreach ($systems as $system) {
                $system->nrJumps = $jumps;
                $solarSystems[] = $system;
            }
        }
        return $solarSystems;
    }
}