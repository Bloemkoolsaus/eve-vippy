<?php
namespace map\model;

class SolarSystem extends \scanning\model\System
{
    /**
     * Find by id
     * @param $id
     * @return \map\model\SolarSystem|null
     */
    public static function findById($id)
    {
        return self::findOne(["solarsystemid" => $id]);
    }

    /**
     * Find one instances
     * @param array $conditions
     * @param array $orderby
     * @return \map\model\SolarSystem|null
     */
    public static function findOne($conditions=[], $orderby=["solarsystemname"])
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
     * @return \map\model\SolarSystem[]
     */
    public static function findAll($conditions=[], $orderby=["solarsystemname"])
    {
        $where = array();
        $params = array();
        foreach ($conditions as $var => $val) {
            $where[] = $var." = ?";
            $params[] = $val;
        }

        $entities = array();
        if ($results = \MySQL::getDB()->getRows("SELECT *
                                                FROM    ".\eve\Module::eveDB().".mapsolarsystems
                                                ".((count($where)>0)?"WHERE ".implode(" AND ", $where):"")."
                                                ".((count($orderby)>0)?"ORDER BY ".implode(",", $orderby):"")
                                        , $params))
        {
            foreach ($results as $result)
            {
                $entity = new \map\model\SolarSystem();
                $entity->load($result);
                $entities[] = $entity;
            }
        }
        return $entities;
    }
}