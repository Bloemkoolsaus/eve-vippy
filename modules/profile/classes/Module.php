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
        if (\User::getUSER()) {
            return true;
        }

        return false;
    }
}