<?php
namespace admin\console;

class Authgroup
{
    function doCleanup($arguments=[])
    {
        \Approot::doCliOutput("Check for and delete inactive authorization groups");
        foreach (\admin\model\AuthGroup::getAuthGroups() as $group) {
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
        // Alleen op de laatste dag van de maand uitrekenen!
        if (date("Y-m-d") == date("Y-m-d", mktime(0,0,0,date("m")+1,0,date("Y"))))
        {
            \Approot::doCliOutput("Calculate new subscriptions");
            $nextMonth = date("Y-m-d", mktime(0,0,0,date("m")+1,5,date("Y")));
            foreach (\admin\model\AuthGroup::getAuthGroups() as $group)
            {
                // Alleen als er voor die maand nog geen subscription is
                if ($group->getSubscription($nextMonth))
                    continue;

                // Alleen als er nog wel actieve mensen zijn
                $activeUsers = $group->getActiveUsers("now");
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
                $subscription->description = count($activeUsers)." Active users in the previous month";
                $subscription->fromdate = date("Y-m-d", mktime(0,0,0,date("m")+1,1,date("Y")));
                $subscription->tilldate = date("Y-m-d", mktime(0,0,0,date("m")+2,0,date("Y")));
                $subscription->amount = $amount;
                $subscription->store();
            }
        }
    }
}