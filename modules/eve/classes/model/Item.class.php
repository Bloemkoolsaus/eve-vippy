<?php
namespace eve\model
{
	class Item
	{
		public $id = 0;
		public $groupID = 0;
		public $name;
		public $description;
		public $mass = 0;
		public $volume = 0;

		private $group = null;
		private $category = null;
		private $slot = null;
		private $prices = null;
		private $attributes = array();

		function __construct($id=false)
		{
			if ($id) {
				$this->id = $id;
				$this->load();
			}
		}

		function load($result=false)
		{
			if (!$result)
			{
				$cacheFileName = "items/".$this->id."/item.json";

				// Eerst in cache kijken
				if ($result = \Cache::file()->get($cacheFileName))
					$result = json_decode($result, true);
				else {
					$result = \MySQL::getDB()->getRow("SELECT * FROM ".\eve\Module::eveDB().".invtypes WHERE typeid = ?", array($this->id));
                    \Cache::file()->set($cacheFileName, json_encode($result));
				}
			}

			if ($result)
			{
				$this->id = $result["typeid"];
				$this->groupID = $result["groupid"];
				$this->name = $result["typename"];
				$this->description = $result["description"];
				$this->mass = $result["mass"];
				$this->volume = $result["volume"];
			}
		}

		/**
		 * Get group
		 * @return \eve\model\ItemGroup
		 */
		function getGroup()
		{
			if ($this->group == null)
				$this->group = new \eve\model\ItemGroup($this->groupID);

			return $this->group;
		}

		/**
		 * Get category
		 * @return \eve\model\ItemCategory
		 */
		function getCategory()
		{
			if ($this->category == null)
				$this->category = $this->getGroup()->getCategory();

			return $this->category;
		}

		function getDescription()
		{
			$description = nl2br($this->description);

			$description = str_replace("<font size='14'>", "<h3>", $description);
			$description = str_replace('<font size="14">', "<h3>", $description);
			$description = str_replace("</font>", "</h3>", $description);

			return $description;
		}

		private function getSlotRequirement()
		{
			if ($this->slot == null)
			{
				if ($result = \MySQL::getDB()->getRow("	SELECT 	e.effectname
														FROM 	".\eve\Module::eveDB().".dgmtypeeffects t
															INNER JOIN ".\eve\Module::eveDB().".dgmeffects e ON e.effectid = t.effectid
														WHERE 	e.effectname IN ('loPower','medPower','hiPower','rigSlot','subSystem')
														AND 	t.typeid = ?"
											, array($this->id)))
				{
					if ($result["effectname"] == "hiPower")
						$this->slot = "high";
					else if ($result["effectname"] == "medPower")
						$this->slot = "med";
					else if ($result["effectname"] == "loPower")
						$this->slot = "low";
					else if ($result["effectname"] == "rigSlot")
						$this->slot = "rig";
					else if ($result["effectname"] == "subSystem")
						$this->slot = "sub";
				}
				else
					$this->slot = "none";
			}

			return $this->slot;
		}

		/**
		 * Is this item a fittable module?
		 * @return boolean
		 */
		function isFittingSlot()
		{
			if ($this->isHighSlot())
				return true;
			if ($this->isMedSlot())
				return true;
			if ($this->isLowSlot())
				return true;
			if ($this->isRig())
				return true;
			if ($this->isSubSystem())
				return true;

			return false;
		}

		function isHighSlot()
		{
			if ($this->getSlotRequirement() == "high")
				return true;
			else
				return false;
		}

		function isMedSlot()
		{
			if ($this->getSlotRequirement() == "med")
				return true;
			else
				return false;
		}

		function isLowSlot()
		{
			if ($this->getSlotRequirement() == "low")
				return true;
			else
				return false;
		}

		function isRig()
		{
			if ($this->getSlotRequirement() == "rig")
				return true;
			else
				return false;
		}

		function isSubSystem()
		{
			if ($this->getSlotRequirement() == "sub")
				return true;
			else
				return false;
		}

		/**
		 * Is this item a drone?
		 * @return boolean
		 */
		function isDrone()
		{
			if ($this->getGroup()->categoryID == 18)
				return true;
			else
				return false;
		}

		/**
		 * Is this item a ship?
		 * @return boolean
		 */
		function isShip()
		{
			if ($this->getGroup()->categoryID == 6)
				return true;
			else
				return false;
		}

		/**
		 * Get price from eve central
		 * @param date $date
		 * @return number
		 */
		function getPrice($date=false)
		{
			if ($this->prices == null)
			{
				$controller = new \eve\controller\Item();
				$this->prices = array();
				foreach ($controller->getItemPrices($this->id) as $date => $price)
				{
					if (count($this->prices) == 0)
						$this->prices["latest"] = $price;

					$this->prices[$date] = $price;
				}
			}

			$date = ($date===false) ? "latest" : date("Y-m-d", strtotime($date));
			if (!isset($this->prices[$date]))
			{
				$controller = new \eve\controller\Item();
				$this->prices[$date] = $controller->fetchPrice($this->id);
			}
			return $this->prices[$date];
		}

		function getAttribute($attributeName)
		{
			if (!isset($this->attributes[$attributeName]))
			{
				$this->attributes[$attributeName] = 0;
				if ($result = \MySQL::getDB()->getRow("	SELECT  valueint, valuefloat
														FROM    ".\eve\Module::eveDB().".dgmtypeattributes e
														    INNER JOIN ".\eve\Module::eveDB().".dgmattributetypes a ON a.attributeID = e.attributeID
														WHERE 	attributename = ?
														AND		typeid = ?
														GROUP BY a.attributeid"
										, array($attributeName, $this->id)))
				{
					if (is_numeric($result["valueint"]))
						$this->attributes[$attributeName] = $result["valueint"];
					else
						$this->attributes[$attributeName] = $result["valuefloat"];
				}
			}

			return $this->attributes[$attributeName];
		}


		/**
		 * Get by name
		 * @param string $name
		 * @return \eve\model\Item|null
		 */
		public static function getByName($name)
		{
			if ($result = \MySQL::getDB()->getRow("SELECT * FROM ".\eve\Module::eveDB().".invtypes WHERE typename = ?", array($name)))
			{
				$item = new \eve\model\Item();
				$item->load($result);
				return $item;
			}

			return null;
		}

		/**
		 * Get by groupid
		 * @param integer $groupID
		 * @param string $orderby|typename
		 * @return \eve\model\Item[]
		 */
		public static function getByGroup($groupID, $orderby="typename")
		{
			$items = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM ".\eve\Module::eveDB().".invtypes
													WHERE groupid = ? ORDER BY ".$orderby
										, array($groupID)))
			{
				foreach ($results as $result)
				{
					$item = new self();
					$item->load($result);
					$items[] = $item;
				}
			}
			return $items;
		}
	}
}
?>