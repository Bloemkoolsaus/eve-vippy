<?php
namespace users\view;

class Register
{
    function getOverview($arguments=[])
    {
        $errors = [];

        if (\Tools::POST("doRegister"))
        {
            // Check if passwords match
            if (!\Tools::POST("password1") || (\Tools::POST("password1") != \Tools::POST("password2"))) {
                $check = false;
                $errors[] = "Passwords do not match.";
            }

            // Check username
            if (\Tools::POST("username")) {
                if ($result = \MySQL::getDB()->getRow("SELECT * FROM users WHERE username = ?", [\Tools::POST("username")])) {
                    $errors[] = "Your chosen username is already taken.";
                    $errors[] = "<br />If you already have a vippy account, you can add other characters, including out-of-corp, to your original account. See below!";
                }
            } else
                $errors[] = "You did not choose a username";

            // Check email adres
            if (\Tools::POST("email")) {
                if ($result = \MySQL::getDB()->getRow("SELECT * FROM users WHERE email = ?", [\Tools::POST("email")])) {
                    $errors[] = "<b>An account with that email adress already exists!</b>";
                    $errors[] = "<br />If you already have a vippy account, you can add other characters, including out-of-corp, to your original account. See below!";
                }
            } else
                $errors[] = "You did not provide a (valid) email adres.";


            // Anti spam check
            if (\Tools::POST("street")) {
                $check = false;
                $errors[] = "Registration failed. Are you a bot?";
            }

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