<?php
namespace map\model;

class Map extends \scanning\model\Chain
{
    /**
     * Is the user allowed to see this map?
     * @param \users\model\User $user=null logged in user
     * @return bool
     */
    function getUserAllowed($user=null)
    {
        if (!$user)
            $user = \User::getUSER();

        // Check sessie
        if ($user->getSession("map_".$this->id."_allowed") !== null)
            return $user->getSession("map_".$this->id."_allowed");

        // Geen sessie. Berekenen.
        $allowed = false;
        foreach ($user->getAvailibleChains() as $chain) {
            if ($chain->id == $this->id) {
                $allowed = true;
                break;
            }
        }
        $user->setSession("map_".$this->id."_allowed", $allowed);
        return $allowed;
    }

    function getURL()
    {
        return $this->id."-".\Tools::formatURL($this->name);
    }

    /**
     * Get home wormhole
     * @return \map\model\Wormhole|null
     */
    function getHomeWormhole()
    {
        if ($this->getHomeSystem())
            return \map\model\Wormhole::getWormholeBySystemID($this->getHomeSystem()->id, $this->id);

        return null;
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
        foreach (self::findAll(["name" => $name]) as $map) {
            if (\User::getUSER()) {
                foreach (\User::getUSER()->getAuthGroups() as $group) {
                    if ($group->id == $map->authgroupID)
                        return $map;
                }
            } else
                return $map;
        }
        return null;
    }



    /**
     * Find (allowed) map by URL
     * @param $url
     * @return \map\model\Map|null
     */
    public static function findByURL($url)
    {
        \AppRoot::doCliOutput("Find map by url: ".$url);
        $findByID = null;
        $parts = explode("-",$url);
        if (is_numeric($parts[0])) {
            // Mogelijk id?
            \AppRoot::debug("Find by id: ".$parts[0]);
            $findByID = $parts[0];
            $map = new \map\model\Map($parts[0]);
            if ($map->getUserAllowed() || (\User::getUSER() && \User::getUSER()->getIsSysAdmin()))
                return $map;

            if (\User::getUSER()) {
                foreach (\User::getUSER()->getAvailibleChains() as $map) {
                    $findURL = $url;
                    if (!$findByID)
                        $findURL = $map->id."-".$url;
                    if ($map->getURL() == $findURL)
                        return $map;
                }
            }
        } else
            \AppRoot::debug("<strong>NO MAP:</strong> Map URL has to be prefixed by the map ID");

        return null;
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
        $where = [];
        $params = [];
        foreach ($conditions as $var => $val) {
            $where[] = $var." = ?";
            $params[] = $val;
        }
        if (!isset($conditions["deleted"]))
            $where[] = "deleted = 0";

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