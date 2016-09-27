<?php
namespace eve\controller
{
	class Item
	{
		protected $db = null;

		function getFittingInvFlagHighSlotIDs()
		{
			$ids = array();
			if ($results = \MySQL::getDB()->getRows("SELECT flagid FROM invflags WHERE flagname LIKE 'HiSlot%'")) {
				foreach ($results as $result) {
					$ids[] = $result["flagid"];
				}
			}
			return $ids;
		}

		function getFittingInvFlagMedSlotIDs()
		{
			$ids = array();
			if ($results = \MySQL::getDB()->getRows("SELECT flagid FROM invflags WHERE flagname LIKE 'MedSlot%'")) {
				foreach ($results as $result) {
					$ids[] = $result["flagid"];
				}
			}
			return $ids;
		}

		function getFittingInvFlagLowSlotIDs()
		{
			$ids = array();
			if ($results = \MySQL::getDB()->getRows("SELECT flagid FROM invflags WHERE flagname LIKE 'LoSlot%'")) {
				foreach ($results as $result) {
					$ids[] = $result["flagid"];
				}
			}
			return $ids;
		}

		function getFittingInvFlagRigSlotIDs()
		{
			$ids = array();
			if ($results = \MySQL::getDB()->getRows("SELECT flagid FROM invflags WHERE flagname LIKE 'RigSlot%'")) {
				foreach ($results as $result) {
					$ids[] = $result["flagid"];
				}
			}
			return $ids;
		}

		function getFittingInvFlagSubsystemSlotIDs()
		{
			$ids = array();
			if ($results = \MySQL::getDB()->getRows("SELECT flagid FROM invflags WHERE flagname LIKE 'SubSystem%'")) {
				foreach ($results as $result) {
					$ids[] = $result["flagid"];
				}
			}
			return $ids;
		}

		function getItemPrices($itemID)
		{
			$prices = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM eve_item_prices WHERE typeid = ? ORDER BY pricedate DESC", array($itemID)))
			{
				foreach ($results as $result) {
					$prices[date("Y-m-d",strtotime($result["pricedate"]))] = $result["sellprice"];
				}
			}
			return $prices;
		}

		function addItemPrice($itemID, $buyPrice, $sellPrice, $date=false)
		{
			if (!$date)
				$date = date("Y-m-d");

			$what = array("typeid"	=> $itemID,
						"buyprice"	=> $buyPrice,
						"sellprice"	=> $sellPrice,
						"pricedate"	=> date("Y-m-d",strtotime($date)));
			$who = array("typeid"	=> $itemID,
						"pricedate"	=> date("Y-m-d",strtotime($date)));
			\MySQL::getDB()->updateinsert("eve_item_prices", $what, $who);
		}

		function fetchPrice($itemID)
		{
			$api = new \api\Client();
			$result = $api->get("http://api.eve-central.com/api/marketstat?typeid=".$itemID."&usesystem=30000142&hours=12");

			if ((int)$result["result"]->marketstat->type->sell->volume == 0)
			{
				// Geen verkopen.. probeer de regio
				$result = $api->get("http://api.eve-central.com/api/marketstat?typeid=".$itemID."&useregion=10000002&hours=12");
			}

			$buyprice = (string)$result["result"]->marketstat->type->buy->avg;
			$sellprice = (string)$result["result"]->marketstat->type->sell->min;

			$this->addItemPrice($itemID, $buyprice, $sellprice);
			return $sellprice;
		}
	}
}
?>