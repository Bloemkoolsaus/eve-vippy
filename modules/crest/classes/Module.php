<?php
namespace crest;

class Module extends \Module
{
    public $moduleName = "crest";
    public $moduleTitle = "CREST";

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