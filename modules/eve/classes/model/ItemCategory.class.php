<?php
namespace eve\model
{
	class ItemCategory
	{
		public $id = 0;
		public $name;
		public $description;

		private $groups = null;

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
				$result = \MySQL::getDB()->getRow("SELECT * FROM ".\eve\Module::eveDB().".invcategories WHERE categoryid = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["categoryid"];
				$this->name = $result["categoryname"];
				$this->description = $result["description"];
			}
		}

		/**
		 * Get groups
		 * @return \eve\model\ItemGroup[]
		 */
		function getGroups()
		{
			if ($this->groups === null)
			{
				$this->groups = array();
				if ($results = \MySQL::getDB()->getRows("SELECT * FROM ".\eve\Module::eveDB().".invgroups WHERE categoryid = ? ORDER BY groupname", array($this->id)))
				{
					foreach ($results as $result)
					{
						$group = new \eve\model\ItemGroup();
						$group->load($result);
						$this->groups[] = $group;
					}
				}
			}

			return $this->groups;
		}





		/**
		 * Get categories related to ship fittings
		 * @return \eve\model\ItemCategory[]
		 */
		public static function getShipModuleCategories()
		{
			$categories = array();
			$relatedCategoryNames = array("Drone","Deployable","Charge","Module","Subsystem");

			if ($results = \MySQL::getDB()->getRows("SELECT *
													FROM ".\eve\Module::eveDB().".invcategories
													WHERE categoryname IN ('".implode("','",$relatedCategoryNames)."')
													ORDER BY categoryname"))
			{
				foreach ($results as $result)
				{
					$cat = new \eve\model\ItemCategory();
					$cat->load($result);
					$categories[] = $cat;
				}
			}
			return $categories;
		}

		/**
		 * Get categories related to ship fittings
		 * @return \eve\model\ItemCategory[]
		 */
		public static function getShipCategories()
		{
			$categories = array();
			$relatedCategoryNames = array("Ship");

			if ($results = \MySQL::getDB()->getRows("SELECT *
													FROM ".\eve\Module::eveDB().".invcategories
													WHERE categoryname IN ('".implode("','",$relatedCategoryNames)."')
													ORDER BY categoryname"))
			{
				foreach ($results as $result)
				{
					$cat = new \eve\model\ItemCategory();
					$cat->load($result);
					$categories[] = $cat;
				}
			}
			return $categories;
		}

		/**
		 * Get categories related to ship fittings
		 * @return \eve\model\ItemCategory[]
		 */
		public static function getSkillCategories()
		{
			$categories = array();
			$relatedCategoryNames = array("Skill");

			if ($results = \MySQL::getDB()->getRows("SELECT *
													FROM ".\eve\Module::eveDB().".invcategories
													WHERE categoryname IN ('".implode("','",$relatedCategoryNames)."')
													ORDER BY categoryname"))
			{
				foreach ($results as $result)
				{
					$cat = new \eve\model\ItemCategory();
					$cat->load($result);
					$categories[] = $cat;
				}
			}
			return $categories;
		}
	}
}
?>