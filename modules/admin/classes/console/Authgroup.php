<?php
namespace admin\console;

class Authgroup
{
    function doCleanup($arguments=[])
    {
        \Approot::doCliOutput("Clean-up authorization groups");

        /** @var \admin\model\AuthGroup[] $authGroups */
        $authGroups = [];
        while (count($arguments) > 0) {
            $authGroups[] = new \admin\model\AuthGroup(array_shift($arguments));
        }
        if (count($authGroups) == 0)
            $authGroups = \admin\model\AuthGroup::getAuthGroups();

        foreach ($authGroups as $group)
        {
            \AppRoot::doCliOutput(" > ".$group->name);
            if ($group->deleted) {
                if ($group->isActive()) {
                    $group->deleted = false;
                    $group->store();
                }
            } else {
                if (!$group->isActive()) {
                    $group->delete();
                }
            }
        }
    }

    function doSubscriptions($arguments=[])
    {
        \Approot::doCliOutput("Calculate new subscriptions");

        $nextMonth = date("Y-m-d", mktime(0,0,0,date("m")+1,5,date("Y")));
        \AppRoot::doCliOutput("> Calculate for ".$nextMonth);

        /** @var \admin\model\AuthGroup[] $authGroups */
        $authGroups = [];
        while (count($arguments) > 0) {
            $authGroups[] = new \admin\model\AuthGroup(array_shift($arguments));
        }
        if (count($authGroups) == 0)
            $authGroups = \admin\model\AuthGroup::getAuthGroups();

        foreach ($authGroups as $group)
        {
            \AppRoot::doCliOutput(" > ".$group->name);
            // Alleen als er voor die maand nog geen subscription is
            if ($group->getSubscription($nextMonth)) {
                \AppRoot::doCliOutput("    - has active subscription. Skip");
                continue;
            }

            // Alleen als er nog wel actieve mensen zijn
            $activeUsers = $group->getActiveUsers();
            if (count($activeUsers) == 0) {
                \AppRoot::doCliOutput("    - no active users. Skip");
                continue;
            }

            // Maak de subscription
            $amount = 0;
            if (count($activeUsers) >= 5) {
                $amount = round(count($activeUsers)/5)*50; // 10m per actieve user, afgerond naar de dichtsbijzinde 50m
                if ($amount > 500)
                    $amount = 500;
            }

            \AppRoot::doCliOutput("    - ".count($activeUsers)." active users. Subscription fee: ".$amount."m");
            $subscription = new \admin\model\Subscription();
            $subscription->authgroupID = $group->id;
            $subscription->fromdate = date("Y-m-d", mktime(0,0,0,date("m")+1,1,date("Y")));
            $subscription->tilldate = date("Y-m-d", mktime(0,0,0,date("m")+2,0,date("Y")));
            $subscription->description = "Vippy ".date("F, Y", strtotime($subscription->fromdate)).": ".count($activeUsers)." active users";
            $subscription->amount = $amount;
            $subscription->store();
        }
    }
}