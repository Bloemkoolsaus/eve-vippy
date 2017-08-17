<?php
class Cache
{
    protected $type;
    protected static $available = null;

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

    function isAvailable()
    {
        if (self::$available === null)
            self::$available = true;

        if (!self::$available)
            \AppRoot::error("Cache method ".$this->type." not available");

        return self::$available;
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

    /**
     * Get memory cache
     * @param int $ttl time-to-live (in seconds)
     * @return Cache
     */
    public static function memory($ttl=86400)
    {
        $cache = self::getCache("memory");
        if (isset($cache->ttl))
            $cache->ttl = $ttl;
        return $cache;
    }
}