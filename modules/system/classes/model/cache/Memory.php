<?php
namespace system\model\cache;

class Memory extends \Cache
{
    protected $type = "memory";
    public $ttl = 86400;

    function get($var)
    {
        if (is_array($var))
            $var = implode("-", $var);
        \AppRoot::debug("Get memory cache: ".$var);

        if (!$this->isAvailable())
            return parent::get($var);

        try {
            $cache = apcu_fetch($var);
            return $cache;
        }
        catch (\Exception $e) {
            \AppRoot::error($e);
        }
        return null;
    }

    function set($var, $val)
    {
        if (!$this->isAvailable())
            return parent::get($var);

        if (is_array($var))
            $var = implode("-",$var);

        \AppRoot::debug("Set memory cache: ".$var);
        $store = apcu_store($var, $val, $this->ttl);
        return $store;
    }

    function remove($var)
    {
        if (!$this->isAvailable())
            return parent::get($var);

        if (is_array($var))
            $var = implode("-",$var);

        return apcu_delete($var);
    }


    function isAvailable()
    {
        if (self::$available === null) {
            self::$available = false;
            if (function_exists("apcu_store"))
                self::$available = true;
        }

        return parent::isAvailable();
    }
}