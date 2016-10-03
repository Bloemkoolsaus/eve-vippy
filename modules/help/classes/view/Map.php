<?php
namespace help\view;

class Map
{
    function getOverview($arguments=[])
    {
        \AppRoot::title("Help - Map");
        $tpl = \SmartyTools::getSmarty();
        return $tpl->fetch("help/map");
    }
}