<?php
namespace vippy\view;

class Costs
{
    function getOverview($arguments=[])
    {
        \AppRoot::title("Costs");
        \User::setUSER(null);
        $tpl = \SmartyTools::getSmarty();
        return $tpl->fetch("vippy/costs");
    }
}