<?php
namespace vippy\view;

class About
{
    function getOverview($arguments=[])
    {
        \AppRoot::title("About");
        \User::setUSER(null);
        $tpl = \SmartyTools::getSmarty();
        return $tpl->fetch("vippy/about");
    }
}