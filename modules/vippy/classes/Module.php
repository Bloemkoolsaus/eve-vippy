<?php
namespace vippy;

class Module extends \Module
{
    public $moduleName = "vippy";
    public $moduleTitle = "Vippy";

    function isAuthorized($arguments=[])
    {
        return true;
    }
}