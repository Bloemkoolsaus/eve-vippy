<?php
namespace help\view;

class Esi
{
    function getOverview($arguments=[])
    {
        \AppRoot::title("Help - ESI");
        $tpl = \SmartyTools::getSmarty();
        return $tpl->fetch("help/esi");
    }
}