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
        $tpl = \SmartyTools::getSmarty();
        return $tpl->fetch("users/login/form");
    }
}