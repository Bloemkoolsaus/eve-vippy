<?php
namespace map\model;

class NamingScheme extends \Model
{
    public $id = 0;
    public $name;
    public $title;
    public $public = false;


    function getNewWormholeName(\scanning\model\Wormhole $system, $ignoreReservations=false)
    {
        return null;
    }



    /**
     * Get object by name
     * @param $name
     * @return \map\model\NamingScheme
     */
    public static function getObjectByName($name)
    {
        $class = '\\map\\model\\namingscheme\\';
        foreach (explode("-",$name) as $part) {
            $class .= ucfirst($part);
        }

        if (!class_exists($class))
            $class = '\\map\\model\\NamingScheme';

        return new $class();
    }

    /**
     * Get naming scheme by id
     * @param      $id
     * @param null $class
     * @return NamingScheme|null
     */
    public static function findByID($id, $class=null)
    {
        if ($result = \MySQL::getDB()->getRow("SELECT * FROM map_namingscheme WHERE id = ?", array($id)))
        {
            $scheme = self::getObjectByName($result["name"]);
            $scheme->load($result);
            return $scheme;
        }

        return null;
    }
}