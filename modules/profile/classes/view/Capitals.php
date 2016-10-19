<?php
namespace profile\view;

class Capitals
{
    function getOverview($arguments = [])
    {
        \AppRoot::title("Profile");
        \AppRoot::title("Capital Ships");
        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("capitals", \profile\model\Capital::findAll(["userid" => \User::getUSER()->id]));
        return $tpl->fetch("profile/capitals/overview");
    }

    function getEdit($arguments = [])
    {
        $capital = new \profile\model\Capital(array_shift($arguments));
        $errors = [];

        if (\Tools::POST("ship"))
        {
            $shipname = explode("(", \Tools::POST("ship"));
            $ship = \eve\model\Ship::getByName(trim($shipname[0]));
            if (!$ship)
                $errors[] = "Ship ".$shipname[0]." not found";

            $systemname = explode("(", \Tools::POST("system"));
            $system = \eve\model\SolarSystem::getSolarsystemByName(trim($systemname[0]));
            if (!$system)
                $errors[] = "Solarsystem ".$systemname[0]." not found";

            if (count($errors) == 0)
            {
                $capital->userID = \User::getUSER()->id;
                $capital->shipID = $ship->id;
                $capital->solarSystemID = $system->id;
                $capital->description = \Tools::POST("description");
                $capital->store();
                \User::getUSER()->resetCache();
                \AppRoot::redirect("profile/capitals");
            }
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("capital", $capital);
        $tpl->assign("errors", $errors);
        return $tpl->fetch("profile/capitals/edit");
    }

    function getDelete($arguments = [])
    {
        $capital = new \profile\model\Capital(array_shift($arguments));
        $capital->delete();
        \AppRoot::redirect("profile/capitals");
    }
}