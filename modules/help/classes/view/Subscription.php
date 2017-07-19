<?php
namespace help\view;

class Subscription
{
    function getOverview($arguments=[])
    {
        \AppRoot::title("Help - Subscription");
        $tpl = \SmartyTools::getSmarty();
        return $tpl->fetch("help/subscription");
    }
}