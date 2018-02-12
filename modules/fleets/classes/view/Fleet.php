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
        $user = \User::getUSER();

        if (\Tools::POST("fleet") || \Tools::POST("boss"))
        {
            if (!\Tools::POST("fleet"))
                $errors[] = "No CREST Link entered";
            if (!\Tools::POST("boss"))
                $errors[] = "No Boss Character selected";

            if (count($errors) == 0)
            {
                try {
                    $console = new \esi\console\Fleet();
                    $fleet = $console->getFleetByURL(\Tools::POST("fleet"), \Tools::POST("boss"));
                    if ($fleet) {
                        $fleet = $console->getFleetMembers($fleet);
                        if ($fleet->active)
                            \AppRoot::redidrectToReferer();
                        $errors[] = $fleet->statusMessage;
                    }
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("popup", \Tools::REQUEST("ajax"));
        $tpl->assign("errors", $errors);

        if (!\Tools::REQUEST("ajax"))
            $tpl->assign("fleets", \fleets\model\Fleet::findAll(["authgroupid" => $user->getCurrentAuthGroupID()]));

        return $tpl->fetch("fleets/add");
    }
}