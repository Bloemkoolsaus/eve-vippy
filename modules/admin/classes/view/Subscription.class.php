<?php
namespace admin\view
{
	class Subscription
	{
		function getEditForm($subscriptiondID)
		{
			$subscription = new \admin\model\Subscription($subscriptiondID);

			if (\Tools::Post("id"))
			{
				$subscription->description = \Tools::POST("description");
				$subscription->authgroupID = \Tools::POST("authgroup");
				$subscription->amount = \Tools::POST("amount");
				$subscription->fromdate = (\Tools::POST("fromdate"))?date("Y-m-d", strtotime(\Tools::POST("fromdate"))):null;
				$subscription->tilldate = (\Tools::POST("tilldate"))?date("Y-m-d", strtotime(\Tools::POST("tilldate"))):null;
				$subscription->store();
				\AppRoot::refresh();
			}

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("subscription", $subscription);
			$tpl->assign("authgroups", \admin\model\AuthGroup::getAuthGroups());
			return $tpl->fetch("admin/subscription/edit");
		}
	}
}
?>