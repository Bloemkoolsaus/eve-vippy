<?php
namespace stats;

class Module extends \Module
{
    public $moduleName = "stats";
    public $moduleTitle = "Statistics";


    function doMaintenance()
    {
        \AppRoot::runCron(["stats", "stats", "calc", "yesterday"]);
        return true;
    }
}