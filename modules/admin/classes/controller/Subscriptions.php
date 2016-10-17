<?php
namespace admin\controller
{
	class Subscriptions
	{
		function getSection()
		{
			$section = new \Section("vippy_subscriptions", "id");

			$section->addElement("Authorization Group", "authgroupid", false, 'admin\\elements\\AuthGroup\\AuthGroup');
			$section->addElement("Description", "description");
			$section->addElement("Amount", "amount");
			$fromdate = $section->addElement("From", "fromdate", false, "DateElement");
			$tilldate = $section->addElement("Till", "tilldate", false, "DateElement");

			$fromdate->todayIfNull = false;
			$fromdate->fullView = true;
			$tilldate->todayIfNull = false;
			$tilldate->fullView = true;

			return $section;
		}
	}
}
?>