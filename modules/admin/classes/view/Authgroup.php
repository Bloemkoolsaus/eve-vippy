<?php
namespace admin\view;

class Authgroup
{
    function getOverview($arguments=[])
    {
        if (!\User::getUSER()->getIsSysAdmin())
            \AppRoot::redirect("admin/authgroup/edit/".\User::getUSER()->getCurrentAuthGroupID());

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("authgroups", \admin\model\AuthGroup::getAuthGroups());
        return $tpl->fetch("admin/authgroups/overview");
    }

    function getNew($arguments=[])
    {
        return $this->getEdit($arguments);
    }

    function getEdit($arguments=[])
    {
        $authgroup = new \admin\model\AuthGroup(array_shift($arguments));
        $errors = array();

        if (!$authgroup->getMayAdmin(\User::getUSER()))
            return "";

        \AppRoot::title($authgroup->name);

        if (\Tools::REQUEST("deletealliance"))
        {
            $authgroup->removeAlliance(\Tools::REQUEST("deletealliance"));
            $authgroup->store();

            // Check of nog wel toegang heeft. Anders ongedaan maken.
            \User::getUSER()->resetAuthGroups();
            if (!$authgroup->getMayAdmin(\User::getUSER()))
            {
                // Oops!
                $authgroup->addAllianceById(\Tools::REQUEST("deletealliance"));
                $authgroup->store();

                $alliance = new \eve\model\Alliance(\Tools::REQUEST("deletealliance"));
                $errors[] = "<b>Cannot remove $alliance->name</b><br />That would revoke your own access to this group.";
            }
            else
                \AppRoot::redirect("/admin/authgroup/edit/".$authgroup->id);
        }

        if (\Tools::REQUEST("deletecorp"))
        {
            $authgroup->removeCorporation(\Tools::REQUEST("deletecorp"));
            $authgroup->store();

            // Check of nog wel toegang heeft. Anders ongedaan maken.
            \User::getUSER()->resetAuthGroups();
            if (!$authgroup->getMayAdmin(\User::getUSER()))
            {
                // Oops!
                $corporation = new \eve\model\Corporation(\Tools::REQUEST("deletecorp"));
                $authgroup->addCorporation($corporation);
                $authgroup->store();
                $errors[] = "<b>Cannot remove $corporation->name</b><br />That would revoke your own access to this group.";
            }
            else
                \AppRoot::redirect("/admin/authgroup/edit/".$authgroup->id);
        }


        if (\Tools::POST("id") || \Tools::POST("name"))
        {
            if (\Tools::POST("name"))
                $authgroup->name = \Tools::POST("name");

            if (\Tools::POST("corporation")) {
                $corporation = new \eve\model\Corporation(\Tools::POST("corporation"));
                if ($corporation->isNPC())
                    $errors[] = "<b>Cannot add $corporation->name</b><br />`".$corporation->name."` is an NPC corp. Vippy only works for player corporations!";
                else
                    $authgroup->addCorporation($corporation);
            }

            if (\Tools::POST("alliance"))
                $authgroup->addAllianceById(\Tools::POST("alliance"));

            if (\Tools::POST("closestsystems")) {
                \MySQL::getDB()->delete("map_closest_systems", ["authgroupid" => $authgroup->id, "userid" => 0]);
                if (isset($_POST["closestsystems"]["systems"])) {
                    foreach ($_POST["closestsystems"]["systems"] as $key => $systemID) {
                        $system = new \map\model\ClosestSystem();
                        $system->solarSystemID = $systemID;
                        $system->authGroupID = $authgroup->id;
                        $system->store();
                    }
                }

                foreach (\map\model\ClosestSystem::getClosestSystemsBySystemID() as $system) {
                    // check of onmap aan is gevinkt.
                    if (isset($system->tradeHub)) {
                        if (!isset($_POST["closestsystems"]["onmap"][$system->solarSystemID])) {
                            $system->authGroupID = $authgroup->id;
                            $system->showOnMap = false;
                            $system->store();
                        }
                    } else {
                        if (isset($_POST["closestsystems"]["onmap"][$system->solarSystemID])) {
                            $system->showOnMap = true;
                            $system->store();
                        }
                    }
                }
            }

            if (\Tools::POST("removeSystem")) {
                \MySQL::getDB()->delete("map_closest_systems", [
                    "solarsystemid" => \Tools::POST("removeSystem"),
                    "authgroupid" => $authgroup->id, "userid" => 0
                ]);
            }

            if (\Tools::POST("addclosesystem")) {
                $system = new \map\model\ClosestSystem();
                $system->solarSystemID = \Tools::POST("addclosesystem");
                $system->authGroupID = $authgroup->id;
                $system->store();
            }

            $authgroup->clearConfig();
            if (\Tools::POST("config")) {
                foreach ($_POST["config"] as $var => $val) {
                    $authgroup->setConfig($var, $val);
                }
            }
            $authgroup->store();

            if (isset($_POST["subscription"])) {
                $subscription = new \admin\model\Subscription();
                $subscription->authgroupID = $authgroup->id;
                $subscription->description = $_POST["subscription"]["description"];
                $subscription->amount = $_POST["subscription"]["amount"];
                $subscription->fromdate = (isset($_POST["subscription"]["fromdate"]))?date("Y-m-d", strtotime($_POST["subscription"]["fromdate"])):null;
                $subscription->tilldate = (isset($_POST["subscription"]["tilldate"]))?date("Y-m-d", strtotime($_POST["subscription"]["tilldate"])):null;
                $subscription->store();
            }

            if (count($errors) == 0)
                \AppRoot::redirect("/admin/authgroup/edit/".$authgroup->id);
        }

        $allianceEdit = new \eve\elements\Alliance("Alliance", "alliance");
        $corporationEdit = new \eve\elements\Corporation("Corporation", "corporation");
        $systemEdit = new \eve\elements\SolarSystem("Solarsystem", "addclosesystem");

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("errors", $errors);
        $tpl->assign("authgroup", $authgroup);
        $tpl->assign("addAllianceElement", $allianceEdit);
        $tpl->assign("addCorporationElement", $corporationEdit);
        $tpl->assign("addSolarsystemElement", $systemEdit);
        return $tpl->fetch("admin/authgroups/edit");
    }

    function getSubscription($arguments=[])
    {
        $authgroup = new \admin\model\AuthGroup(array_shift($arguments));

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("authgroup", $authgroup);
        return $tpl->fetch("admin/authgroups/subscription");
    }
}