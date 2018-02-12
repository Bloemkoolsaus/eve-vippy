<?php
namespace notices;

class Module extends \Module
{
    public $moduleName = "notices";
    public $moduleTitle = "Notifications";
    public $public = false;


    function doMaintenance()
    {
        // Expired notifications opruimen
        \AppRoot::doCliOutput("Clean up old notifications");
        \MySQL::getDB()->doQuery("delete from notices where expiredate < '".date("Y-m-d")." 00:00:00'");
    }
}