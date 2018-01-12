<?php
namespace users\view;

class Login
{
    function getOverview($arguments=[])
    {
        return $this->getLogin($arguments);
    }

    function getLogin($arguments=[])
    {
        \AppRoot::addStylesheetFile("modules/users/css/login.css");

        $errors = [];
        $warnings = [];
        $noAccount = null;

        if (count($arguments) > 0) {
            $action = array_shift($arguments);
            if ($action == "no-account") {
                $noAccount = new \eve\model\Character(array_shift($arguments));
            }
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("errors", $errors);
        $tpl->assign("warnings", $warnings);
        $tpl->assign("noAccount", $noAccount);
        return $tpl->fetch("users/login/form");
    }
}