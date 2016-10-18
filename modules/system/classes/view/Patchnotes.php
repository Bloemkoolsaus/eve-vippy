<?php
namespace system\view;

class Patchnotes
{
    function getOverview($argments=[])
    {
        $controller = new \system\controller\PatchNotes();
        $controller->registerPatchNotes(\User::getUSER());
        \User::getUSER()->resetCache();

        $numericNaming = false;
        $dirAdminEnabled = false;
        foreach (\User::getUSER()->getAuthGroups() as $group) {
            if ($group->getConfig("wh_naming_numeric"))
                $numericNaming = true;
            if ($group->getConfig("dir_admin_disabled"))
                $dirAdminEnabled = true;
        }

        $notes = \SmartyTools::getSmarty();
        $notes->assign("numeric_naming", $numericNaming);
        $notes->assign("dir_admin_enabled", $dirAdminEnabled);

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("notes", $notes->fetch("documents/changelog.txt"));
        return $tpl->fetch("system/patchnotes");
    }
}