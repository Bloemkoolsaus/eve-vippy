<?php
namespace crest\view;

class Fleet
{
    function getAdd($arguments=[])
    {

        $tpl = \SmartyTools::getSmarty();
        return $tpl->fetch("crest/fleet/add");
    }
}