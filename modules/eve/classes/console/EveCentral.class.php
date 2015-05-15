<?php
namespace eve\console
{
	class EveCentral
	{
		function import()
		{
			\AppRoot::setMaxExecTime(500);

			$itemController = new \eve\controller\Item();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM eve_item_prices WHERE pricedate < ?
													ORDER BY pricedate ASC LIMIT 25"
									, array(date("Y-m-d"))))
			{
				foreach ($results as $result) {
					$itemController->fetchPrice($result["typeid"]);
				}
			}

			return true;
		}
	}
}
?>