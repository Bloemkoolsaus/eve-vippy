<?php
namespace users\view;

class Register
{
    function getOverview($arguments=[])
    {
        return $this->getRegister($arguments);
    }

    function getRegister($arguments=[])
    {
        $errors = [];

        if (\Tools::POST("character"))
        {
            $rememberLogin = (\Tools::COOKIE("remember-after-sso")) ? true : false;

            $character = new \crest\model\Character(\Tools::POST("character"));
            $user = $character->getUser();
            if (!$user)
            {
                $user = new \users\model\User();
                $user->username = $character->name;
                $user->store();

                $character->userID = $user->id;
                $character->store();
            }

            $character->importData();
            $user->setLoginStatus(true, $rememberLogin);

            $controller = new \system\controller\PatchNotes();
            $controller->registerPatchNotes($user);
        }

        \AppRoot::redirect("/");
        return true;
    }
}