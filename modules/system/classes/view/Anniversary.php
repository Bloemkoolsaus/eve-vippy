<?php
namespace system\view;

class Anniversary
{
    function getOverview($argments=[])
    {
        $tpl = \SmartyTools::getSmarty();
        return $tpl->fetch("system/anniversary");
    }
}