<?php
namespace admin\view
{
	class Subscription
	{
		function getEditForm($subscriptiondID)
		{
			$subscription = new \admin\model\Subscription($subscriptiondID);

			if (\Tools::Post("authgroup"))
			{
				$subscription->description = \Tools::POST("description");
				$subscription->authgroupID = \Tools::POST("authgroup");
				$subscription->amount = \Tools::POST("amount");
				$subscription->fromdate = (\Tools::POST("fromdate"))?date("Y-m-d", strtotime(\Tools::POST("fromdate"))):null;
				$subscription->tilldate = (\Tools::POST("tilldate"))?date("Y-m-d", strtotime(\Tools::POST("tilldate"))):null;
				$subscription->store();
				\AppRoot::redirect("index.php?module=admin&section=authgroups&admin=1&action=edit&id=".$subscription->authgroupID);
			}


			if (\Tools::REQUEST("authgroup"))
				$subscription->authgroupID = \Tools::REQUEST("authgroup");

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("subscription", $subscription);
			$tpl->assign("authgroups", \admin\model\AuthGroup::getAuthGroups());
			return $tpl->fetch("admin/subscription/edit");
		}
	}
}
?>