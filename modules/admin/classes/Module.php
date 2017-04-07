<?php
namespace admin;

class Module extends \Module
{
    public $moduleName = "admin";
    public $moduleTitle = "Admin";
    public $public = false;

    function doMaintenance()
    {
        $console = new \map\console\Map();
        $console->cleanupSignatures();
        $console->cleanupWormholes();
        return true;
    }
}