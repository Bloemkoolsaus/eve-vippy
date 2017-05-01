<?php
namespace vippy\view;

class About
{
    function getOverview($arguments=[])
    {
        \User::setUSER(null);
        $tpl = \SmartyTools::getSmarty();
        return $tpl->fetch("vippy/about");
    }
}