<?php
namespace map\model;

class System extends \scanning\model\System
{
    private static $_currentSystem = null;

    /**
     * Get current system
     * @return \map\model\System|null
     */
    public static function getCurrentSystem()
    {
        if (self::$_currentSystem === null)
            self::$_currentSystem = new \map\model\System(\User::getSelectedSystem());

        return self::$_currentSystem;
    }
}