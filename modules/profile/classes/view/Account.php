<?php
namespace profile\view;

class Account
{
    function getOverview($arguments = [])
    {
        \AppRoot::title("Profile");
        \AppRoot::title("Account Settings");

        if (\Tools::POST("save"))
        {
            \User::getUSER()->username = \Tools::POST("username");
            \User::getUSER()->email = \Tools::POST("email");

            if (\Tools::POST("password1") || \Tools::POST("password2")) {
                if (\Tools::POST("password1") == \Tools::POST("password2"))
                    \User::getUSER()->password = \User::generatePassword(\Tools::POST("password1"));
                else
                    $errors[] = "Passwords did not match";
            }

            // Settings
            foreach ($_POST["setting"] as $id => $value) {
                $setting = \users\model\Setting::findById($id);
                \User::getUSER()->setSetting($setting, $value);
            }

            \User::getUSER()->store();
            \AppRoot::refresh();
        }


        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("user", \User::getUSER());
        $tpl->assign("settings", \users\model\Setting::findAll());
        return $tpl->fetch("profile/account/settings");
    }
}