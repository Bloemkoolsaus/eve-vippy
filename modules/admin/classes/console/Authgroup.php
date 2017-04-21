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

    function doBalance($arguments=[])
    {
        \AppRoot::doCliOutput("Check authorization groups balance");

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

        }
    }

    function doSubscriptions($arguments=[])
    {
        // Alleen op de laatste dag van de maand uitrekenen!
        if (date("Y-m-d") == date("Y-m-d", mktime(0,0,0,date("m")+1,0,date("Y"))))
        {
            \Approot::doCliOutput("Calculate new subscriptions");
            $nextMonth = date("Y-m-d", mktime(0,0,0,date("m")+1,5,date("Y")));

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
                if ($group->getSubscription($nextMonth))
                    continue;

                // Alleen als er nog wel actieve mensen zijn
                $activeUsers = $group->getActiveUsers();
                if (count($activeUsers) == 0)
                    continue;

                // Maak de subscription
                $amount = (floor((count($activeUsers)/10)*2)*2)*100;
                if ($amount > 500)
                    $amount = 500;
                if ($amount < 100)
                    $amount = 100;

                $subscription = new \admin\model\Subscription();
                $subscription->authgroupID = $group->id;
                $subscription->description = count($activeUsers)." Active users in ".date("F");
                $subscription->fromdate = date("Y-m-d", mktime(0,0,0,date("m")+1,1,date("Y")));
                $subscription->tilldate = date("Y-m-d", mktime(0,0,0,date("m")+2,0,date("Y")));
                $subscription->amount = $amount;
                $subscription->store();
            }
        }
    }
}