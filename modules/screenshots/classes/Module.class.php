<?php
namespace screenshots
{
    class Module extends \Module
    {
        public $moduleName = "screenshots";
        public $moduleTitle = "Screenshots";

        public function getContent()
        {
            $tpl = \SmartyTools::getSmarty();
            echo $tpl->fetch("screenshots/index");
            exit;
        }
    }
}
?>