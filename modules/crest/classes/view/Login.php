<?php
namespace crest\view;

class Login
{
    function getOverview($arguments=[])
    {
        if (\Tools::POST("login") == "sso") {
            if (\Tools::POST("remember")) {
                \Tools::setCOOKIE("remember-after-sso", 1);
            }
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

        try {
            if ($state && $code) {
                $crest = new \crest\Login();
                $crest->getToken($state, $code);
            }
        } catch (\Exception $e) {
            \AppRoot::error($e->getMessage());
        } finally {
            \AppRoot::debug("no state/code. exit");
            \AppRoot::redirect("/");
        }
    }
}