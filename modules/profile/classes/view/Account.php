<?php
namespace profile\view;

class Account
{
    function getOverview($arguments = [])
    {
        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("user", \User::getUSER());
        $tpl->assign("settings", \users\model\Setting::findAll());
        return $tpl->fetch("profile/account/settings");
    }
}