<?php
\MySQL::getDB()->doQuery("truncate vippy_subscriptions");

foreach (\admin\model\AuthGroup::getAuthGroups() as $group)
{
    $subscription = new \admin\model\Subscription();
    $subscription->authgroupID = $group->id;
    $subscription->amount = 0;
    $subscription->description = "Vippy 5 year anniversary!";
    $subscription->fromdate = "2017-05-01";
    $subscription->tilldate = "2017-08-31";
    $subscription->store();
}