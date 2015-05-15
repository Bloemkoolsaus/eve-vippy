<?php
namespace eve\model
{
	class ShipType extends \eve\model\ItemGroup
	{
		/**
		 * Get ship based on avarage mass in group
		 * @return \eve\model\Ship
		 */
		function getShipBasedOnAvarageMass()
		{
			\AppRoot::debug("getShipBasedOnAvarageMass(".$this->name.")");

			$ships = \eve\model\Ship::getByGroup($this->id, "mass");
			$key = round(count($ships)/2);

			\AppRoot::debug($ships[$key]);
			return $ships[$key];
		}

		/**
		 * Get ship types
		 * @return \eve\model\ShipType[]
		 */
		public static function getShipTypes()
		{
			$ids = array();
			foreach (\eve\model\Ship::getShips() as $ship)
			{
				if (!in_array($ship->groupID, $ids))
					$ids[] = $ship->groupID;
			}

			$types = array();
			if ($results = \MySQL::getDB()->getRows("SELECT *
													FROM 	".\eve\Module::eveDB().".invgroups
													WHERE 	groupid IN (".implode(",",$ids).")
													ORDER BY groupname"))
			{
				foreach ($results as $result)
				{
					$type = new \eve\model\ShipType();
					$type->load($result);
					$types[] = $type;
				}
			}
			return $types;
		}
	}
}
?>