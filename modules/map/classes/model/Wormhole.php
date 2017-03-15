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

    /**
     * Find wormhole by coordinates
     * @param $x
     * @param $y
     * @param \map\model\Map $map
     * @return \map\model\Wormhole|null
     */
    public static function findByCoordinates($x, $y, \map\model\Map $map)
    {
        \AppRoot::debug("findWormholeByCoordinates($x,$y,$map->id)");
        $leftX = $x;
        $topY = $y;

        $rightX = $x + \Config::getCONFIG()->get("map_wormhole_width");
        $botY = $y + \Config::getCONFIG()->get("map_wormhole_height");

        if ($result = \MySQL::getDB()->getRow("	SELECT 	*
                                                FROM 	mapwormholes
                                                WHERE	chainid = ".$map->id."
                                                AND		((x = ".$leftX." AND y = ".$topY.")
                                                    OR		(x BETWEEN ".$leftX." AND ".$rightX."
                                                        AND	y BETWEEN ".$topY." AND ".$botY.")
                                                    OR		(".$leftX." BETWEEN x AND (x+".\Config::getCONFIG()->get("map_wormhole_width").")
                                                        AND	".$botY." BETWEEN y AND (y+".\Config::getCONFIG()->get("map_wormhole_height")."))
                                                    OR		(".$topY." BETWEEN y AND (y+".\Config::getCONFIG()->get("map_wormhole_height").")
                                                        AND	(x+".\Config::getCONFIG()->get("map_wormhole_width").") BETWEEN ".$leftX." AND ".$rightX.")
                                                    OR		(".$topY." BETWEEN y AND (y+".\Config::getCONFIG()->get("map_wormhole_height").")
                                                        AND	x BETWEEN ".$leftX." AND ".$rightX."))"))
        {
            $wormhole = new \map\model\Wormhole();
            $wormhole->load($result);
            return $wormhole;
        }

        return null;
    }
}