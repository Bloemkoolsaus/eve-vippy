<?php
namespace map\model;

class Map extends \scanning\model\Chain
{
    function getUserAllowed($user=null)
    {
        if (!$user)
            $user = \User::getUSER();

        foreach ($user->getAvailibleChains() as $chain) {
            if ($chain->id == $this->id)
                return true;
        }

        return false;
    }

    /**
     * Find by id
     * @param $id
     * @return \map\model\Map|null
     */
    public static function findById($id)
    {
        return self::findOne(["id" => $id]);
    }

    /**
     * Find map by name
     * @param $name
     * @return \map\model\Map|null
     */
    public static function findByName($name)
    {
        return self::findOne(["name" => $name]);
    }

    /**
     * Find one instances
     * @param array $conditions
     * @param array $orderby
     * @return \map\model\Map|null
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
     * @return \map\model\Map[]
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
                                                FROM    mapwormholechains
                                                ".((count($where)>0)?"WHERE ".implode(" AND ", $where):"")."
                                                ".((count($orderby)>0)?"ORDER BY ".implode(",", $orderby):"")
            , $params))
        {
            foreach ($results as $result)
            {
                $entity = new \map\model\Map();
                $entity->load($result);
                $entities[] = $entity;
            }
        }
        return $entities;
    }
}