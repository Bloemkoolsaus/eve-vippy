<?php
namespace map\model;

class Connection extends \scanning\model\Connection
{
    private $_map;



    /**
     * Get map
     * @return \map\model\Map
     */
    function getMap()
    {
        if ($this->_map == null)
            $this->_map = new \map\model\Map($this->chainID);

        return $this->_map;
    }

    function delete()
    {
        if (\AppRoot::isCommandline() || \User::getUSER()->isAllowedChainAction($this->getMap(), "delete"))
        {
            \MySQL::getDB()->delete("mapwormholeconnections", ["id" => $this->id]);
            $this->getMap()->setMapUpdateDate(); // Update chain cache timer
        }
    }

    /**
     * Add jump
     * @param integer $shiptypeID
     * @param integer $pilotID |null
     * @param bool $copy
     * @return bool
     */
    function addJump($shiptypeID, $pilotID=null, $copy=false)
    {
        \AppRoot::debug("addJump($shiptypeID, $pilotID, $copy)");
        if (!$this->getFromSystem() || !$this->getToSystem())
            return false;

        $ship = new \eve\model\Ship($shiptypeID);
        \MySQL::getDB()->insert("mapwormholejumplog", [
            "connectionid"	=> $this->id,
            "chainid"		=> $this->getMap()->id,
            "fromsystemid"	=> $this->getFromSystem()->id,
            "tosystemid"	=> $this->getToSystem()->id,
            "characterid"	=> $pilotID,
            "shipid"		=> $ship->id,
            "mass"          => $ship->mass,
            "jumptime"		=> date("Y-m-d H:i:s")
        ]);

        // Check the same connection on other maps.
        if ($copy) {
            foreach (\map\model\Connection::getConnectionByLocationsAuthGroup(
                $this->getFromWormhole()->solarSystemID,
                $this->getToWormhole()->solarSystemID,
                $this->getMap()->authgroupID) as $connection)
            {
                if ($connection->id !== $this->id)
                    $connection->addJump($shiptypeID, $pilotID, false);
            }
        }
        return true;
    }

    function addMass($amount, $copy=false)
    {
        $data = [
            "connectionid"  => $this->id,
            "chainid"		=> $this->getMap()->id,
            "fromsystemid"  => $this->getFromSystem()->id,
            "tosystemid"	=> $this->getToSystem()->id,
            "characterid"	=> null,
            "shipid"		=> null,
            "mass"          => $amount,
            "jumptime"	    => date("Y-m-d H:i:s")
        ];
        \MySQL::getDB()->insert("mapwormholejumplog", $data);
        \User::getUSER()->addLog("addmass", $this->id, $data);

        // Check the same connection on other maps.
        if ($copy) {
            foreach (\scanning\model\Connection::getConnectionByLocationsAuthGroup(
                $this->getFromWormhole()->solarSystemID,
                $this->getToWormhole()->solarSystemID,
                $this->getMap()->authgroupID) as $connection)
            {
                if ($connection->id !== $this->id)
                    $connection->addMass($amount, false);
            }
        }
    }

    /**
     * Get expiredate of this connection
     * @return string datetime
     */
    function getExpireDate()
    {
        $lifetime = 24;
        if ($this->getWormholeType() && $this->getWormholeType()->lifetime)
            $lifetime = $this->getWormholeType()->lifetime;

        if ($this->eol) {
            return date("Y-m-d H:i:s", mktime(
                date("H", strtotime($this->lifetimeUpdateDate))+4,
                date("i", strtotime($this->lifetimeUpdateDate)),
                date("s", strtotime($this->lifetimeUpdateDate)),
                date("m", strtotime($this->lifetimeUpdateDate)),
                date("d", strtotime($this->lifetimeUpdateDate)),
                date("Y", strtotime($this->lifetimeUpdateDate))
            ));
        }

        return date("Y-m-d H:i:s", mktime(
            date("H", strtotime($this->addDate))+$lifetime,
            date("i", strtotime($this->addDate)),
            date("s", strtotime($this->addDate)),
            date("m", strtotime($this->addDate)),
            date("d", strtotime($this->addDate)),
            date("Y", strtotime($this->addDate))
        ));
    }

    /**
     * Get expire status
     * @return string expired|eol|null
     */
    function getExpireStatus()
    {
        $expireDate = $this->getExpireDate();
        if (strtotime($expireDate) < strtotime("now"))
            return "expired";

        $eolDate = date("Y-m-d H:i:s", mktime(
            date("H", strtotime($expireDate))-4,
            date("i", strtotime($expireDate)),
            date("s", strtotime($expireDate)),
            date("m", strtotime($expireDate)),
            date("d", strtotime($expireDate)),
            date("Y", strtotime($expireDate))
        ));
        if (strtotime($eolDate) < strtotime("now"))
            return "eol";

        return null;
    }

    /**
     * Connection expired?
     * @return bool
     */
    function isExpired()
    {
        if (strtotime($this->getExpireDate()) < strtotime("now"))
            return true;

        return false;
    }




    /**
     * Find one instances
     * @param array $conditions
     * @param array $orderby
     * @return \map\model\Connection|null
     */
    public static function findOne($conditions=[], $orderby=["name"])
    {
        $objects = self::findAll($conditions, $orderby);
        if (count($objects) > 0)
            return $objects[0];

        return null;
    }

    /**
     * Find all instances
     * @param array $conditions
     * @param array $orderby
     * @return \map\model\Connection[]
     */
    public static function findAll($conditions=[], $orderby=["adddate"])
    {
        $where = [];
        $params = [];
        foreach ($conditions as $var => $val) {
            $where[] = $var." = ?";
            $params[] = $val;
        }

        $entities = array();
        if ($results = \MySQL::getDB()->getRows("SELECT *
                                                FROM    mapwormholeconnections
                                                ".((count($where)>0)?"WHERE ".implode(" AND ", $where):"")."
                                                ".((count($orderby)>0)?"ORDER BY ".implode(",", $orderby):"")
                                            , $params))
        {
            foreach ($results as $result) {
                $entity = new \map\model\Connection();
                $entity->load($result);
                $entities[] = $entity;
            }
        }
        return $entities;
    }


    /**
     * Get connection
     * @param integer $fromSystemID
     * @param integer $toSystemID
     * @param integer $mapID
     * @return \map\model\Connection|NULL
     */
    public static function getConnectionByLocations($fromSystemID, $toSystemID, $mapID)
    {
        if ($result = \MySQL::getDB()->getRow("
                                SELECT	c.*
                                FROM	mapwormholeconnections c
                                    INNER JOIN mapwormholes wf ON wf.chainid = c.chainid AND c.fromwormholeid = wf.id
                                    INNER JOIN mapwormholes wt ON wt.chainid = c.chainid AND c.towormholeid = wt.id
                                WHERE	((wf.solarsystemid = ? AND wt.solarsystemid = ?)
                                    OR	(wf.solarsystemid = ? AND wt.solarsystemid = ?))
                                AND		c.chainid = ?
                                GROUP BY c.id"
                    , [$fromSystemID, $toSystemID, $toSystemID, $fromSystemID, $mapID]))
        {
            $connection = new \map\model\Connection();
            $connection->load($result);
            return $connection;
        }
        return null;
    }

    /**
     * Get connection
     * @param integer $fromSystemID
     * @param integer $toSystemID
     * @param integer $authGroupID
     * @return \map\model\Connection[]
     */
    public static function getConnectionByLocationsAuthGroup($fromSystemID, $toSystemID, $authGroupID)
    {
        $connections = array();
        if ($results = \MySQL::getDB()->getRows("SELECT	c.*
                                                FROM	mapwormholeconnections c
                                                    INNER JOIN mapwormholechains ch ON ch.id = c.chainid
                                                    INNER JOIN mapwormholes wf ON wf.chainid = c.chainid AND c.fromwormholeid = wf.id
                                                    INNER JOIN mapwormholes wt ON wt.chainid = c.chainid AND c.towormholeid = wt.id
                                                WHERE	((wf.solarsystemid = ? AND wt.solarsystemid = ?)
                                                    OR	(wf.solarsystemid = ? AND wt.solarsystemid = ?))
                                                AND		ch.authgroupid = ?
                                                GROUP BY c.id"
                        , [$fromSystemID, $toSystemID, $toSystemID, $fromSystemID, $authGroupID]))
        {
            foreach ($results as $result)
            {
                $connection = new \map\model\Connection();
                $connection->load($result);
                $connections[] = $connection;
            }
        }

        return $connections;
    }

    /**
     * Get connection
     * @param integer $fromWhID
     * @param integer $toWhID
     * @param integer $chainID
     * @return \map\model\Connection|NULL
     */
    public static function getConnectionByWormhole($fromWhID, $toWhID, $chainID)
    {
        if ($result = \MySQL::getDB()->getRow("	SELECT	*
                                                FROM	mapwormholeconnections
                                                WHERE	((fromwormholeid = ? AND towormholeid = ?)
                                                    OR	(fromwormholeid = ? AND towormholeid = ?))
                                                AND		chainid = ?
                                                GROUP BY id"
                        , [$fromWhID, $toWhID, $toWhID, $fromWhID, $chainID]))
        {
            $connection = new \map\model\Connection();
            $connection->load($result);
            return $connection;
        }
        return null;
    }
}