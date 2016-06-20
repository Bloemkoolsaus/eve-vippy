<?php
namespace map\view
{
    class Wormhole
    {
        function getOverview($arguments = array())
        {
            return $this->getMove($arguments);
        }

        function getMove($arguments=array())
        {
            if (count($arguments) > 0)
            {
                $wormhole = new \map\model\Wormhole(array_shift($arguments));
                $wormhole->move(\Tools::REQUEST("x"),\Tools::REQUEST("y"));
            }

            return "true";
        }

        function getDetails($arguments=array())
        {
            $wormhole = new \map\model\Wormhole(array_shift($arguments));

            $tpl = \SmartyTools::getSmarty();
            $tpl->assign("wormhole", $wormhole);
            return $tpl->fetch("map/wormhole/details");
        }

        function getActivity($arguments=array())
        {
            $wormhole = new \map\model\Wormhole(array_shift($arguments));
            return json_encode(["url" => $wormhole->getSolarsystem()->buildActivityGraph(),
                                "date" => "<b>Graph age:</b> &nbsp; ".strtolower(\Tools::getAge($wormhole->getSolarsystem()->getActivityGraphAge()))]);
        }
    }
}