<?php
class Cache
{
    protected $type;

    function get($var)
    {
        return null;
    }

    function set($var, $val)
    {
        return null;
    }

    function remove($var)
    {
        return null;
    }


    /**
     * Get file cache
     * @param $type
     * @return \Cache
     */
    public static function getCache($type)
    {
        $class = '\\system\\model\\cache\\'.ucfirst($type);
        if (class_exists($class))
            return new $class();

        return new \Cache();
    }

    /**
     * Get file cache
     * @return \system\model\cache\File
     */
    public static function file()
    {
        return self::getCache("file");
    }
}