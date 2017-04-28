<?php
namespace admin;

class Module extends \Module
{
    public $moduleName = "admin";
    public $moduleTitle = "Admin";
    public $public = false;

    function doMaintenance()
    {
        $console = new \admin\console\Authgroup();
        $console->doCleanup();
        $console->doBalance();
        $console->doSubscriptions();
        return true;
    }
}