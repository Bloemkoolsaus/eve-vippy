<?php
namespace vippy\view;

class Features
{
    function getOverview($arguments=[])
    {
        \AppRoot::title("Features");
        \User::setUSER(null);
        $tpl = \SmartyTools::getSmarty();
        return $tpl->fetch("vippy/features");
    }
}