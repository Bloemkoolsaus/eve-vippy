<?php
namespace help\view;

class Crest
{
    function getOverview($arguments=[])
    {
        \AppRoot::title("Help - Crest");
        $tpl = \SmartyTools::getSmarty();
        return $tpl->fetch("help/crest");
    }
}