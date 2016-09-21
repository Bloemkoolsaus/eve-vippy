<?php
namespace crest\view;

class Fleet
{
    function getAdd($arguments=[])
    {
        if (\Tools::POST("fleet"))
        {

            $console = new \crest\console\Fleet();
            $fleet = $console->getFleetByURL(\Tools::POST("fleet"), \Tools::POST("boss"));
            if ($fleet)
                $console->doFleet([$fleet->id]);

        }

        $tpl = \SmartyTools::getSmarty();
        return $tpl->fetch("crest/fleet/add");
    }
}