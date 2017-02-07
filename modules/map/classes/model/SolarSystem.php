<?php
namespace map\model;

class SolarSystem extends \scanning\model\System
{
    private $_notifications;

    /**
     * Get notifications
     * @param \map\model\Map $map
     * @return \notices\model\Notice[]
     */
    function getNotifications(\scanning\model\Chain $map)
    {
        if ($this->_notifications === null)
        {
            $this->_notifications = [];

            // Build query
            $queryParts = [
                "n.deleted = 0",
                "n.solarsystemid = ".$this->id,
                "n.expiredate > '".date("Y-m-d H:i:s")."'",
                "n.authgroupid = ".$map->authgroupID,
                "(r.userid is null or n.persistant > 0)"
            ];

            // Exec query
            if ($results = \MySQL::getDB()->getRows("SELECT	n.*
                                                    FROM	notices n
                                                        LEFT JOIN notices_read r ON r.noticeid = n.id AND r.userid = ?
                                                    WHERE 	".implode(" AND ", $queryParts)."
                                                    GROUP BY n.id"
                                            , [\User::getUSER()->id]))
            {
                foreach ($results as $result)
                {
                    $notice = new \notices\model\Notice();
                    $notice->load($result);
                    $this->_notifications[] = $notice;
                }
            }
        }

        return $this->_notifications;
    }


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