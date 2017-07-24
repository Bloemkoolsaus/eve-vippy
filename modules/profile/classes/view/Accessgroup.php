<?php
namespace profile\view;

class Accessgroup
{
    function getOverview($arguments=[])
    {
        $errors = [];
        \AppRoot::title("New Access Group");

        // Pak de maand over 3 weken.
        $subscrEndDate = mktime(0,0,0,date("m"),date("d")+21,date("Y"));
        $subscrEndDate = date("Y-m-d", mktime(0,0,0,date("m", $subscrEndDate)+1,0,date("Y", $subscrEndDate)));

        if (\Tools::POST("name"))
        {
            // Check of een van de corp/alliances niet al in een group staan..
            if (isset($_POST["alliances"])) {
                foreach ($_POST["alliances"] as $id => $on) {
                    $groups = \admin\model\AuthGroup::getAuthgroupsByAlliance($id);
                    if (count($groups) > 0) {
                        $alliance = new \eve\model\Alliance($id);
                        $errors[] = "Alliance ".$alliance->name." is already part of an access-group!";
                    }
                }
            }
            if (isset($_POST["corporations"])) {
                foreach ($_POST["corporations"] as $id => $on) {
                    $groups = \admin\model\AuthGroup::getAuthgroupsByCorporation($id);
                    if (count($groups) > 0) {
                        $corp = new \eve\model\Corporation($id);
                        $errors[] = "Corporation ".$corp->name." is already part of an access-group!";
                    }
                }
            }

            if (count($errors) == 0)
            {
                $authgroup = new \admin\model\AuthGroup();
                $authgroup->name = \Tools::POST("name");
                $authgroup->store();

                $authgroup->setConfig("fleet_warning", 1);

                if (isset($_POST["alliances"])) {
                    foreach ($_POST["alliances"] as $id => $on) {
                        $authgroup->addAllianceById($id);
                    }
                }
                if (isset($_POST["corporations"])) {
                    foreach ($_POST["corporations"] as $id => $on) {
                        $authgroup->addCorporationById($id);
                    }
                }
                $authgroup->store();

                // Tradehubs toevoegen
                \AppRoot::debug("Add tradeubs");
                foreach (\map\model\SolarSystem::getTradehubs() as $system) {
                    \AppRoot::debug(" > ".$system->name);
                    $hub = new \map\model\ClosestSystem();
                    $hub->authGroupID = $authgroup->id;
                    $hub->solarSystemID = $system->id;
                    $hub->store();
                }

                // Trial subscription
                $subscription = new \admin\model\Subscription();
                $subscription->description = "Free trial";
                $subscription->amount = 0;
                $subscription->authgroupID = $authgroup->id;
                $subscription->fromdate = date("Y-m-d");
                $subscription->tilldate = $subscrEndDate;
                $subscription->store();

                // Admin Usergroup
                $usergroup = new \users\model\UserGroup();
                $usergroup->name = "VIPPY Admin";
                $usergroup->authGroupID = $authgroup->id;
                $usergroup->addRight("admin", "admin", "Vippy Admin");
                $usergroup->store();

                \User::getUSER()->addUserGroup($usergroup->id);
                \User::getUSER()->store();

                \AppRoot::redirect("admin/authgroup/edit/".$authgroup->id);
            }
        }

        $alliances = [];
        $corporations = [];
        foreach (\User::getUSER()->getCharacters() as $character) {
            if ($character->getCorporation()) {
                $corporations[$character->getCorporation()->id] = $character->getCorporation();
                if ($character->getCorporation()->getAlliance())
                    $alliances[$character->getCorporation()->getAlliance()->id] = $character->getCorporation()->getAlliance();
            }
        }

        $newName = null;
        foreach ($alliances as $alliance) {
            $newName = $alliance->name;
            break;
        }
        if (!$newName) {
            foreach ($corporations as $corp) {
                $newName = $corp->name;
                break;
            }
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("errors", $errors);
        $tpl->assign("newname", $newName);
        $tpl->assign("alliances", $alliances);
        $tpl->assign("corporations", $corporations);
        $tpl->assign("trialdate", $subscrEndDate);
        return $tpl->fetch("profile/accessgroup/new");
    }
}