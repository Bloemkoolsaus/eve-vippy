<?php
namespace map\view;

class Rally
{
    function getAdd($arguments=[])
    {
        $wormhole = new \map\model\Wormhole(array_shift($arguments));
        $wormhole->rally = true;
        $wormhole->store();
        return true;
    }
    function getRemove($arguments=[])
    {
        $wormhole = new \map\model\Wormhole(array_shift($arguments));
        $wormhole->rally = false;
        $wormhole->store();
        return true;
    }
}