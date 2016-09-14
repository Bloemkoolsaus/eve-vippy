<?php
namespace users\view;

class Register
{
    function getOverview($arguments=[])
    {
        $errors = [];

        if (\Tools::POST("username"))
        {
            // Check if passwords match
            if (!\Tools::POST("password1") || (\Tools::POST("password1") != \Tools::POST("password2"))) {
                $check = false;
                $errors[] = "Passwords do not match.";
            }

            // Check username
            if (\Tools::POST("username")) {
                if ($result = \MySQL::getDB()->getRow("SELECT * FROM users WHERE username = ?", [\Tools::POST("username")])) {
                    $errors[] = "<b>Your chosen username is already taken</b>";
                    $errors[] = "If you already have a vippy account, you can add other characters via CREST. See the profile page when logged in to Vippy!";
                }
            } else
                $errors[] = "You did not choose a username";

            // Check email adres (spammers)
            if (\Tools::POST("email"))
                $errors[] = "Vippy does not need your email adress!";


            if (count($errors) == 0)
            {
                $user = new \users\model\User();
                $user->username = \Tools::POST("username");
                $user->displayname = \Tools::POST("username");
                $user->password = \User::generatePassword(\Tools::POST("password1"));
                $user->email = \Tools::POST("email");
                $user->store();
                $user->setLoginStatus();
                \AppRoot::redirect("/");
            }
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("errors", $errors);
        return $tpl->fetch("users/register/form");
    }
}