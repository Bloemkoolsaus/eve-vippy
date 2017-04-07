<?php
namespace admin\view;

class Subscription
{
    function getNew($arguments=[])
    {
        $authgroup = new \admin\model\AuthGroup(array_shift($arguments));
        $subscription = new \admin\model\Subscription();
        $subscription->authgroupID = $authgroup->id;

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("subscription", $subscription);
        return $tpl->fetch("admin/subscription/edit");
    }

    function getEdit($arguments=[])
    {
        $subscription = new \admin\model\Subscription(array_shift($arguments));

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("subscription", $subscription);
        return $tpl->fetch("admin/subscription/edit");
    }

    function getStore($arguments=[])
    {
        $authgroup = new \admin\model\AuthGroup(\Tools::POST("authgroup"));
        $subscription = new \admin\model\Subscription(\Tools::POST("id"));
        $subscription->authgroupID = $authgroup->id;
        $subscription->description = \Tools::POST("description");
        $subscription->amount = \Tools::POST("amount");
        $subscription->fromdate = date("Y-m-d", strtotime(\Tools::POST("fromdate")));
        $subscription->tilldate = date("Y-m-d", strtotime(\Tools::POST("tilldate")));
        $subscription->store();
        \AppRoot::redirect("admin/authgroup/edit/".$authgroup->id);
    }
}