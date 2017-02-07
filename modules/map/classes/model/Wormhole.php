<?php
namespace map\model;

class Wormhole extends \scanning\model\Wormhole
{
    private $_solarsystem;
    private $_map;

    /**
     * Get map
     * @return \map\model\Map|null
     */
    function getMap()
    {
        if ($this->_map === null)
            $this->_map = new \map\model\Map($this->chainID);

        return $this->getMap();
    }


    /**
     * Get solarsystem
     * @return \map\model\SolarSystem|null
     */
    function getSolarsystem()
    {
        if ($this->_solarsystem == null && $this->solarSystemID > 0)
            $this->_solarsystem = new \map\model\SolarSystem($this->solarSystemID);

        return $this->_solarsystem;
    }


    /**
     * Find by id
     * @param $id
     * @return \map\model\Wormhole|null
     */
    public static function findById($id)
    {
        return self::findOne(["id" => $id]);
    }

    /**
     * Find one instances
     * @param array $conditions
     * @param array $orderby
     * @return \map\model\Wormhole|null
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
     * @return \map\model\Wormhole[]
     */
    public static function findAll($conditions=[], $orderby=["name"])
    {
        $where = array();
        $params = array();
        foreach ($conditions as $var => $val) {
            $where[] = $var." = ?";
            $params[] = $val;
        }

        $entities = array();
        if ($results = \MySQL::getDB()->getRows("SELECT *
                                                FROM    mapwormholes
                                                ".((count($where)>0)?"WHERE ".implode(" AND ", $where):"")."
                                                ".((count($orderby)>0)?"ORDER BY ".implode(",", $orderby):"")
            , $params))
        {
            foreach ($results as $result)
            {
                $entity = new \map\model\Wormhole();
                $entity->load($result);
                $entities[] = $entity;
            }
        }
        return $entities;
    }
}