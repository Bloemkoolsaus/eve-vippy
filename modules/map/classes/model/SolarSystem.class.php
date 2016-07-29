<?php
namespace map\model;

class SolarSystem extends \scanning\model\System
{


    /**
     * Get current solar system
     * @return \map\model\SolarSystem
     */
    public static function getCurrentSystem()
    {
        $system = new \map\model\SolarSystem(\User::getSelectedSystem());
        return $system;
    }
}