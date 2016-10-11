<?php
namespace map\model;

/**
 * Class Route
 *  Find all wormholes / connections between two systems on the map
 */
class Route
{
    private $map = null;
    private $solarSystemFrom = null;
    private $solarSystemTo = null;
    private $wormholes = null;
    private $connections = null;


    /**
     * Get map
     * @return \map\model\Map|null
     */
    function getMap()
    {
        return $this->map;
    }

    /**
     * Set map
     * @param \map\model\Map $map
     */
    function setMap(\map\model\Map $map)
    {
        $this->map = $map;
    }


    /**
     * get From system
     * @return \map\model\SolarSystem
     */
    function getFromSystem()
    {
        return $this->solarSystemFrom;
    }

    /**
     * set From system
     * @param \map\model\SolarSystem $system
     */
    function setFromSystem(\map\model\SolarSystem $system)
    {
        $this->solarSystemFrom = $system;
    }


    /**
     * get From system
     * @return \map\model\SolarSystem
     */
    function getToSystem()
    {
        return $this->solarSystemTo;
    }

    /**
     * set To system
     * @param \map\model\SolarSystem $system
     */
    function setToSystem(\map\model\SolarSystem $system)
    {
        $this->solarSystemTo = $system;
    }


    /**
     * Get wormholes
     * @return \map\model\Wormhole[]
     */
    function getWormholes()
    {
        if ($this->wormholes === null)
        {
            $route = $this->getRoute();
            if ($route) {
                $this->wormholes = [];
                foreach ($route as $id) {
                    $this->wormholes[] = new \map\model\Wormhole($id);
                }
                $this->wormholes[] = \map\model\Wormhole::findOne([
                    "solarsystemid" => $this->getFromSystem()->id,
                    "chainid" => $this->getMap()->id
                ]);
                $this->wormholes = array_reverse($this->wormholes);
            }
        }

        return $this->wormholes;
    }

    /**
     * get Connctions
     * @return \map\model\Connection[]|null
     */
    function getConnections()
    {
        if ($this->connections === null)
        {
            $wormholes = $this->getWormholes();
            if ($wormholes) {
                $this->connections = [];
                foreach ($wormholes as $key => $wormhole) {
                    if (isset($wormholes[$key+1])) {
                        $connection = \map\model\Connection::getConnectionByLocations($wormhole->solarSystemID, $wormholes[$key+1]->solarSystemID, $this->getMap()->id);
                        if ($connection)
                            $this->connections[] = $connection;
                    }
                }
            }
        }

        return $this->connections;
    }


    /**
     * get Route
     * @return array|null
     */
    private function getRoute()
    {
        $wormholeFrom = \map\model\Wormhole::findOne([
            "solarsystemid" => $this->getFromSystem()->id,
            "chainid" => $this->getMap()->id
        ]);
        $wormholeTo = \map\model\Wormhole::findOne([
            "solarsystemid" => $this->getToSystem()->id,
            "chainid" => $this->getMap()->id
        ]);

        if ($wormholeFrom)
        {
            $data = $this->getConnectedWormholes($wormholeFrom);
            $route = $this->getkeypath($data, $wormholeTo->id);
            return $route;
        }
        return null;
    }

    private function getkeypath($arr, $lookup)
    {
        if (array_key_exists($lookup, $arr["connections"])) {
            return array($lookup);
        } else {
            foreach ($arr["connections"] as $key => $subarr) {
                if (is_array($subarr)) {
                    $ret = $this->getkeypath($subarr, $lookup);
                    if ($ret) {
                        $ret[] = $key;
                        return $ret;
                    }
                }
            }
        }
        return null;
    }

    private function getConnectedWormholes(\scanning\model\Wormhole $wormhole, $backlist=[], $route=null)
    {
        $data = [
            "id" => $wormhole->id,
            "name" => ($wormhole->getSolarsystem())?$wormhole->getSolarsystem()->name:"",
            "title" => $wormhole->name,
            "connections" => []
        ];
        if (!$route)
            $route = [$wormhole->name];

        $backlist[] = $wormhole->solarSystemID;
        foreach ($wormhole->getConnections() as $connection) {
            $nextHole = ($connection->getFromWormhole()->id != $wormhole->id) ? $connection->getFromWormhole() : $connection->getToWormhole();
            if (!in_array($nextHole->solarSystemID, $backlist)) {
                if ($nextHole != null) {
                    $data["connections"][$nextHole->id] = $this->getConnectedWormholes($nextHole, $backlist, $route);
                    $route[] = $nextHole->name;
                }
            }
        }

        return $data;
    }
}