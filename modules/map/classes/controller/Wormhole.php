<?php
namespace map\controller;

class Wormhole
{
    /**
     * Add a wormhole
     * @param \map\model\Map $map
     * @param int $fromSystemID
     * @param int $toSystemID
     * @return bool
     */
    function addWormhole(\map\model\Map $map, $fromSystemID, $toSystemID=null)
    {
        \AppRoot::doCliOutput("=== addWormhole($fromSystemID, $toSystemID)", true);
        if ($fromSystemID == 0 || $toSystemID == 0)
            return false;

        // Wormholes alleen toevoegen als deze nog niet bestaan.
        $fromWormhole = \map\model\Wormhole::findOne(["solarsystemid" => $fromSystemID, "chainid" => $map->id]);
        $toWormhole = \map\model\Wormhole::findOne(["solarsystemid" => $toSystemID, "chainid" => $map->id]);


        /*
         * Beide systemen staan er nog niet.
         * Voeg toe helemaal onder aan de chain op de map.
         */
        if (!$fromWormhole && !$toWormhole)
        {
            \AppRoot::doCliOutput("both systems are not on yet!");
            $system = new \map\model\Wormhole();
            $system->solarSystemID = $fromSystemID;
            $system->chainID = $map->id;
            $system->x = 50;
            $system->y = 50;

            if ($result = \MySQL::getDB()->getRow("select max(y) as y from mapwormholes where chainid = ?", [$map->id]))
                $system->y = $result["y"] + \scanning\Wormhole::$defaultHeight + \scanning\Wormhole::$defaultOffset + 20;

            $system->store();

            // Bron systeem toegevoegd. Voer functie nog een keer uit voor de bestemming.
            return $this->addWormhole($map, $fromSystemID, $toSystemID);
        }


        // Check welke er nog niet op de map staat, en welke dus toegevoegd moet worden!
        $originHole = null;
        $addingHole = null;
        $addSystemID = null;

        if ($fromWormhole == null) {
            $originHole = $toWormhole;
            $addingHole = $fromWormhole;
            $addSystemID = $fromSystemID;
        } else if ($toWormhole == null) {
            $originHole = $fromWormhole;
            $addingHole = $toWormhole;
            $addSystemID = $toSystemID;
        } else {
            $originHole = $fromWormhole;
            $addingHole = $toWormhole;
        }

        // Voeg toe aan map
        if ($addSystemID !== null)
        {
            \AppRoot::doCliOutput("Voeg toe aan map");
            $position = $this->getNewPosition($map, $originHole);

            $addingHole = new \map\model\Wormhole();
            $addingHole->chainID = $map->id;
            $addingHole->solarSystemID = $addSystemID;
            $addingHole->x = $position["x"];
            $addingHole->y = $position["y"];
            $addingHole->store();
        }

        // Verbinding toevoegen
        if ($originHole && $addingHole)
        {
            \AppRoot::doCliOutput("Verbinding toevoegen");
            $connection = \map\model\Connection::getConnectionByWormhole($originHole->id, $addingHole->id, $map->id);
            if ($connection == null) {
                $connection = new \map\model\Connection();
                $connection->fromWormholeID = $originHole->id;
                $connection->toWormholeID = $addingHole->id;
                $connection->chainID = $map->id;
                $connection->store();
            }
            else
                \AppRoot::doCliOutput("Connection already exists");
        }
        else
            \AppRoot::doCliOutput("Not both holes were added..");

        // Nieuw systeem is toegevoegd.
        if ($addSystemID !== null)
        {
            // Wat zou de naam moeten zijn?
            if ($map->getSetting("wh-autoname-scheme") > 0)
            {
                $reservation = null;
                $wormholeName = $this->getNewName($map, $addingHole, true);

                // Check of die naam gereserveerd is
                foreach ($map->getWormholes() as $whole)
                {
                    if ($whole->isReservation()
                        && (strtolower($whole->name) == strtolower($wormholeName))
                        && ($whole->id != $addingHole->id))
                    {
                        $reservation = $whole;
                        $reservation->name = $wormholeName;
                        $reservation->store();
                    }
                }

                if ($reservation !== null)
                {
                    \AppRoot::debug("Reservation: <pre>" . print_r($reservation, true) . "</pre>");
                    \AppRoot::debug("AddingHole: <pre>" . print_r($addingHole, true) . "</pre>");

                    $addingHole->delete();
                    $reservation->solarSystemID = $addingHole->solarSystemID;
                    $reservation->store();

                    // Verbinding op properties zetten.
                    $connection = \scanning\model\Connection::getConnectionByWormhole($reservation->id, $originHole->id, $map->id);
                    if ($connection !== null)
                    {
                        if (($reservation->getSolarsystem() !== null && $reservation->getSolarsystem()->isCapitalCapable()) &&
                            ($originHole->getSolarsystem() !== null && $originHole->getSolarsystem()->isCapitalCapable()))
                        {
                            $connection->allowCapitals = true;
                            $connection->store();
                        } else {
                            $connection->allowCapitals = false;
                            $connection->store();
                        }

                        if (($reservation->getSolarsystem() !== null && $reservation->getSolarsystem()->isFrigateOnly()) &&
                            ($originHole->getSolarsystem() !== null && $originHole->getSolarsystem()->isFrigateOnly()))
                        {
                            $connection->frigateHole = true;
                            $connection->store();
                        }
                    }
                } else {
                    $addingHole->name = $this->getNewName($map, $addingHole);
                    $addingHole->store();
                }
            }
        }

        $map->setMapUpdateDate();
        return true;
    }

    function addWormholeBySignature(\map\model\Map $map, \map\model\Signature $signature)
    {
        \AppRoot::debug("addWormholeToMap($map->name, $signature->sigID)");

        // Geen naam opgegeven
        if (strlen(trim($signature->sigInfo)) == 0) {
            \AppRoot::debug("cancel: no-name");
            return false;
        }
        // Copy paste van probe scanner
        if (strtolower(trim($signature->sigInfo)) == "unstable wormhole") {
            \AppRoot::debug("cancel: copy-paste from scanner");
            return false;
        }
        // wh back. Negeren.
        if (strtolower(trim($signature->sigInfo)) == "back") {
            \AppRoot::debug("cancel: back");
            return false;
        }

        // Check if this signature already has a (unmapped) wormhole
        \AppRoot::debug("Check if this signature already has a (unmapped) wormhole");
        $newWormhole = \map\model\Wormhole::findOne(["chainid" => $map->id, "signatureid" => $signature->id]);
        if ($newWormhole) {
            if ($newWormhole->getSolarsystem() !== null)
                $newWormhole = null;
        }
        \AppRoot::debug("found wormhole: <pre>".print_r($newWormhole,true)."</pre>");

        // Nieuwe naam
        $originWormhole = \map\model\Wormhole::findOne(["solarsystemid" => $signature->solarSystemID, "chainid" => $map->id]);
        $newWormholeName = $signature->sigInfo;
        \AppRoot::debug("new wormhole name: ".$newWormholeName);
        $parsedWormholeName = explode(" ", $newWormholeName);
        $parts = explode("-", $parsedWormholeName[0]);
        if (count($parts) > 1) {
            if ($originWormhole !== null) {
                if (strtolower($parts[0]) == strtolower($originWormhole->name) || (trim($parts[0]) == "0" && $originWormhole->isHomeSystem())) {
                    $originName = array_shift($parts);
                    $newWormholeName = implode("-", $parts);
                }
            }
        }

        // Terug naar home. Home staat er meestal al wel op!
        if (trim($newWormholeName) == "0" || strtolower(trim($newWormholeName)) == "home")
            return true;

        // Check of de naam van deze wormhole al op de map staat.
        foreach ($map->getWormholes() as $wormhole) {
            if (trim(strtolower($wormhole->name)) == trim(strtolower($newWormholeName))) {
                \AppRoot::debug("already exists: ".$signature->sigInfo);
                return true;
            }
        }

        // Staat nog niet op de kaart! Toevoegen!
        if ($newWormhole !== null) {
            $newWormhole->name = $newWormholeName;
            $newWormhole->store();
        } else {
            $position = $this->getNewPosition($map, $originWormhole);
            $newWormhole = new \map\model\Wormhole();
            $newWormhole->chainID = $map->id;
            $newWormhole->name = $newWormholeName;
            $newWormhole->signatureID = $signature->id;
            $newWormhole->x = $position["x"];
            $newWormhole->y = $position["y"];
            $newWormhole->store();
        }

        // Connectie toevoegen
        $newConnection = \map\model\Connection::getConnectionByWormhole($originWormhole->id, $newWormhole->id, $map->id);
        if ($newConnection == null) {
            $newConnection = new \map\model\Connection();
            $newConnection->fromWormholeID = $originWormhole->id;
            $newConnection->toWormholeID = $newWormhole->id;
            $newConnection->chainID = $map->id;
            $newConnection->store();
        }
        if (count($parsedWormholeName) > 1) {
            for ($i=1; $i<count($parsedWormholeName); $i++) {
                switch (strtolower($parsedWormholeName[$i])) {
                    case "frig":
                        $newConnection->frigateHole = true;
                        break;
                    case "eol":
                        $newConnection->eol = true;
                        break;
                    case "reduced":
                        $newConnection->mass = 1;
                        break;
                    case "crit":
                        $newConnection->mass = 2;
                        break;
                }
            }
        }
        $newConnection->store();
        return true;
    }

    /**
     * Get nieuwe positie voor een wormhole
     * @param \map\model\Map $map
     * @param \map\model\Wormhole|null $origin Vanaf welke wormhole?
     * @return array
     */
    function getNewPosition(\map\model\Map $map, \map\model\Wormhole $origin=null)
    {
        $position = new \map\controller\positioning\Center($map);
        $position->setOrigin($origin);
        return $position->getNextPosition();
    }

    /**
     * Get nieuwe naam voor een wormhole
     * @param \map\model\Map $map
     * @param \map\model\Wormhole $wormhole
     * @param bool $ignoreReservations
     * @return string|boolean false
     */
    function getNewName(\map\model\Map $map, \map\model\Wormhole $wormhole, $ignoreReservations=false)
    {
        $namingScheme = \map\model\NamingScheme::findByID($map->getSetting("wh-autoname-scheme"));
        if ($namingScheme != null) {
            $wormholeName = $namingScheme->getNewWormholeName($wormhole, $ignoreReservations);
            if ($wormholeName != null)
                return $wormholeName;
        }

        return false;
    }
}