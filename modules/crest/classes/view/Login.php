<?php
namespace crest\view;

class Login
{
    function getOverview($arguments=[])
    {
        if (\Tools::POST("login") == "sso") {
            $crest = new \crest\Login();
            $crest->loginSSO("/");
        }
        return "CREST LOGIN";
    }

    function getLogin($arguments=[])
    {
        $state = null;
        $code = null;
        if (count($arguments) > 0)
            $state = array_shift($arguments);
        if (count($arguments) > 0)
            $code = array_shift($arguments);

        if ($state && $code) {
            $crest = new \crest\Login();
            $crest->getToken($state, $code);
        }
        \AppRoot::debug("no state/code. exit");
        \AppRoot::redirect("/");
    }
}