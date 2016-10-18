<?php
namespace admin;

class Module extends \Module
{
    public $moduleName = "admin";
    public $moduleTitle = "Admin";
    public $public = false;

    function getContent()
    {
        $section = (\Tools::REQUEST("section"))?:"overview";
        $action = (\Tools::REQUEST("action"))?:"overview";


        if ($section == "clearcache")
        {
            if (\User::getUSER()->hasRight("admin","sysadmin"))
            {
                $map = new \scanning\console\Map();
                $map->cleanupCache();
                \AppRoot::redirect("index.php?module=scanning");
            }
        }

        if ($section == "subscriptions")
        {
            if (\User::getUSER()->getIsSysAdmin())
            {
                if ($action == "edit" || $action == "new")
                {
                    $view = new \admin\view\Subscription();
                    return $view->getEditForm(\Tools::REQUEST("id"));
                }

                $controller = new \admin\controller\Subscriptions();
                $this->moduleSection = $controller->getSection();
            }
            else
                \AppRoot::redirect("index.php");
        }


        return parent::getContent();
    }
}