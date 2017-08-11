<?php
namespace admin;

class Module extends \Module
{
    public $moduleName = "admin";
    public $moduleTitle = "Admin";
    public $public = false;

    function doMaintenance()
    {
        // Laatste dag van de maand?
        if (date("Y-m-d") == date("Y-m-d", mktime(0,0,0,date("m")+1,0,date("Y")))) {
            \AppRoot::runCron(["admin", "authgroup", "subscriptions"]);
            \AppRoot::runCron(["admin", "authgroup", "cleanup"]);
        }

        return true;
    }
}