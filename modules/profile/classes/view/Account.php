<?php
namespace profile\view;

class Account
{
    function getOverview($arguments = [])
    {
        \AppRoot::title("Profile");
        \AppRoot::title("Account Settings");

        if (\Tools::POST("save") == "account") {
            // Settings
            foreach ($_POST["setting"] as $id => $value) {
                $setting = \users\model\Setting::findOne(["name" => $id]);
                \User::getUSER()->setSetting($setting, $value);
            }
            \User::getUSER()->store();

            if (\Tools::POST("maincharacter"))
                \User::getUSER()->setMainCharacter(\Tools::POST("maincharacter"));

            \AppRoot::refresh();
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("user", \User::getUSER());
        return $tpl->fetch("profile/account");
    }
}