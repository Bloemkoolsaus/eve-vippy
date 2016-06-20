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
     * Find map by name
     * @param $name
     * @return \map\model\Map|null
     */
    public static function findByName($name)
    {
        if ($result = \MySQL::getDB()->getRow("SELECT * FROM mapwormholechains WHERE name = ?", array($name)))
        {
            $map = new \map\model\Map();
            $map->load($result);
            return $map;
        }

        return null;
    }
}