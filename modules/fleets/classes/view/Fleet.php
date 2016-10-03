<?php
namespace fleets\view;

class Fleet
{
    function getOverview($arguments=[])
    {
        $fleets = \fleets\model\Fleet::findAll(["authgroupid" => \User::getUSER()->getCurrentAuthGroupID()]);

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("fleets", $fleets);
        return $tpl->fetch("fleets/overview");
    }

    function getAdd($arguments=[])
    {
        $errors = [];

        if (\Tools::POST("fleet") || \Tools::POST("boss"))
        {
            if (!\Tools::POST("fleet"))
                $errors[] = "No CREST Link entered";

            if (!\Tools::POST("boss"))
                $errors[] = "No Boss Character selected";

            if (count($errors) == 0)
            {
                $console = new \crest\console\Fleet();
                $fleet = $console->getFleetByURL(\Tools::POST("fleet"), \Tools::POST("boss"));
                if ($fleet)
                {
                    $fleet = $console->getFleetMembers($fleet);
                    if ($fleet->active)
                        \AppRoot::redidrectToReferer();

                    $errors[] = $fleet->statusMessage;
                }
                else
                    $errors[] = "Failed getting fleet info from CREST. Make sure you have a valid CREST login in Vippy.";
            }
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("popup", \Tools::REQUEST("ajax"));
        $tpl->assign("errors", $errors);
        return $tpl->fetch("fleets/add");
    }
}