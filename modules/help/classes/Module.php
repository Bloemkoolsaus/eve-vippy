<?php
namespace help
{
    class Module extends \Module
    {
        public $moduleName = "help";
        public $moduleTitle = "Help";

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
}