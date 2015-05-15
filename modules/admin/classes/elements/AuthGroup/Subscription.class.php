<?php
namespace admin\elements\AuthGroup
{
	class Subscription extends \NumberElement\Element
	{
		function getValue($dbvalue=false)
		{
			$subscription = \admin\model\Subscription::getSubscriptionsByAuthgroup($this->value);
			foreach ($subscription as $sub) {
				if ($sub->isActive())
				{
					if ($sub->amount == 0)
						return "<div style='text-align: center;'><i>free license</i></div>";

					return "<b>".$sub->amount."m</b> &nbsp; p/month &nbsp;";
				}
			}

			return "";
		}
	}
}
?>