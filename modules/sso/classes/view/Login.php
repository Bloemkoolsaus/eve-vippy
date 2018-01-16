<?php
namespace sso\view;

class Login
{
    function getOverview($arguments=[])
    {
        if (\Tools::POST("login") == "sso") {
            $crest = new \sso\Login();
            $crest->loginSSO((\AppRoot::getRefererURL())?:"/", (\Tools::POST("remember"))?true:false);
        }

        // Terug naar login formulier
        $login = new \users\view\Login();
        return $login->getLogin();
    }

    function getLogin($arguments=[])
    {
        $state = (\Tools::GET("state"))?:null;
        $code = (\Tools::GET("code"))?:null;

        try {
            if ($state && $code) {
                $sso = new \sso\Login();
                $sso->verify($state, $code);
            }
        } catch (\Exception $e) {
            \AppRoot::error($e->getMessage());
        } finally {
            \AppRoot::redirect("/");
        }
    }
}