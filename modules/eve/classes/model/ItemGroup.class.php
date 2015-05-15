<?php
namespace eve\model
{
	class ItemGroup
	{
		public $id = 0;
		public $categoryID = 0;
		public $name;
		public $description;

		private $category = null;
		private $items = null;

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
				$result = \MySQL::getDB()->getRow("SELECT * FROM ".\eve\Module::eveDB().".invgroups WHERE groupid = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["groupid"];
				$this->categoryID = $result["categoryid"];
				$this->name = $result["groupname"];
				$this->description = $result["description"];
			}
		}

		/**
		 * Get item category
		 * @return \eve\model\ItemCategory
		 */
		function getCategory()
		{
			if ($this->category == null)
				$this->category = new \eve\model\ItemCategory($this->categoryID);

			return $this->category;
		}

		function getItems()
		{
			if ($this->items === null)
			{
				$this->items = array();
				if ($results = \MySQL::getDB()->getRows("SELECT * FROM ".\eve\Module::eveDB().".invtypes WHERE groupid = ? ORDER BY typename", array($this->id)))
				{
					foreach ($results as $result)
					{
						$item = new \eve\model\Item();
						$item->load($result);
						$this->items[] = $item;
					}
				}
			}

			return $this->items;
		}
	}
}
?>