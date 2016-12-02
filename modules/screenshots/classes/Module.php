<?php
namespace screenshots;

class Module extends \Module
{
    public $moduleName = "screenshots";
    public $moduleTitle = "Screenshots";

    public function getContent()
    {
        \User::setUSER(null);
        $tpl = \SmartyTools::getSmarty();
        return $tpl->fetch("screenshots/index");
    }
}