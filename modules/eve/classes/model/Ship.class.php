<?php
namespace eve\model
{
	class Ship extends \eve\model\Item
	{
		private $nrHighSlots = null;
		private $nrMedSlots = null;
		private $nrLowSlots = null;
		private $nrRigSlots = null;
		private $nrSubSystems = null;

		function getShipType()
		{
			return $this->getGroup()->name;
		}

		private function getSlotLayout()
		{
			if ($results = $this->db->getRows("SELECT t.attributename, COALESCE(d.valueInt, d.valueFloat) AS amount
											FROM	".\eve\Module::eveDB().".dgmtypeattributes d
												INNER JOIN ".\eve\Module::eveDB().".dgmattributetypes t ON d.attributeid = t.attributeid
											WHERE	d.typeID = ?
											AND		t.attributename IN ('lowSlots', 'medSlots', 'hiSlots',
																		'rigSlots', 'maxSubSystems')"
							, array($this->id)))
			{
				foreach ($results as $result)
				{
					$this->nrHighSlots = 0;
					$this->nrMedSlots = 0;
					$this->nrLowSlots = 0;
					$this->nrRigSlots = 0;
					$this->nrSubSystems = 0;

					if (strtolower($result["attributename"]) == "hislots")
						$this->nrHighSlots = $result["amount"];
					if (strtolower($result["attributename"]) == "medslots")
						$this->nrMedSlots = $result["amount"];
					if (strtolower($result["attributename"]) == "lowslots")
						$this->nrLowSlots = $result["amount"];
					if (strtolower($result["attributename"]) == "rigslots")
						$this->nrRigSlots = $result["amount"];
					if (strtolower($result["attributename"]) == "maxsubsystems")
						$this->nrSubSystems = $result["amount"];
				}
			}
		}

		function getNrHighSlots()
		{
			if ($this->nrHighSlots == null)
				$this->getSlotLayout();
			return $this->nrHighSlots;
		}

		function getNrMedSlots()
		{
			if ($this->nrMedSlots == null)
				$this->getSlotLayout();
			return $this->nrMedSlots;
		}

		function getNrLowSlots()
		{
			if ($this->nrLowSlots == null)
				$this->getSlotLayout();
			return $this->nrLowSlots;
		}

		function getNrRigSlots()
		{
			if ($this->nrRigSlots == null)
				$this->getSlotLayout();
			return $this->nrRigSlots;
		}

		function getNrSubsystemSlots()
		{
			if ($this->nrSubSystems == null)
				$this->getSlotLayout();
			return $this->nrSubSystems;
		}

		/**
		 * Get max jump range
		 * @param string $jumpDriveCalibration
		 * @return number lightyears
		 */
		function getMaxJumprange($jumpDriveCalibration=false)
		{
			if (!$jumpDriveCalibration)
				$jumpDriveCalibration = 4;

			if ($jumpDriveCalibration > 5)
				$jumpDriveCalibration = 5;

			// Kijk eerst in de cache?
			$cacheFile = "ships/".$this->id."/maxJumpRange.json";
			if ($cache = \AppRoot::getCache($cacheFile))
				$data = json_decode($cache,true);
			else
			{
				// Uitrekenen
				$baseRange = $this->getAttribute("jumpDriveRange");
				$data = array("baseRange" => $baseRange);
				$jdc = \eve\model\Skill::getJumpDriveCalibration();
				$bonusRange = $jdc->getAttribute("jumpDriveRangeBonus");
				for ($i=1; $i<=5; $i++) {
					$data["bonusRange"][$i] = $baseRange + ($baseRange*(($bonusRange*$i)/100));
				}
				\AppRoot::setCache($cacheFile, json_encode($data));
			}

			return $data["bonusRange"][$jumpDriveCalibration];
		}



		/**
		 * Get ships
		 * @return \eve\model\Ship[]
		 */
		public static function getShips()
		{
			$ships = array();
			if ($results = \MySQL::getDB()->getRows("SELECT	i.*
													FROM	".\eve\Module::eveDB().".invtypes i
														INNER JOIN ".\eve\Module::eveDB().".invgroups g ON g.groupid = i.groupid
													WHERE	g.categoryid = 6
													AND		i.published > 0
													ORDER BY i.typename ASC"))
			{
				foreach ($results as $result)
				{
					$ship = new \eve\model\Ship();
					$ship->load($result);
					$ships[] = $ship;
				}
			}

			return $ships;
		}
	}
}
?>