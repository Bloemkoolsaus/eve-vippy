<?php
namespace profile;

class Module extends \Module
{
    public $moduleName = "profile";
    public $moduleTitle = "Profile";

    /**
     * Mag de user deze module zien?
     * @param array $arguments
     * @return bool
     */
    function isAuthorized($arguments=[])
    {
        if (!parent::isAuthorized($arguments)) {
            if (count($arguments) > 0) {
                if ($arguments[0] == "account")
                    return true;
                if ($arguments[0] == "accessgroup")
                    return true;
            }
            return false;
        }
        return true;
    }
}