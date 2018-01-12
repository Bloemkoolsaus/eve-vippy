<?php
namespace sso;

class Module extends \Module
{
    public $moduleName = "sso";
    public $moduleTitle = "eve sso";

    /**
     * Mag de user deze module zien?
     * @param array $arguments
     * @return bool
     */
    function isAuthorized($arguments=[])
    {
        return true;
    }
}