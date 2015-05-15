<?php
namespace eve\model
{
	class ItemFlag
	{
		public $id = 0;
		public $name;
		public $title;

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
				$result = \MySQL::getDB()->getRow("SELECT * FROM ".\eve\Module::eveDB().".invflags WHERE flagid = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["flagid"];
				$this->name = $result["flagname"];
				$this->title = $result["flagtext"];
			}
		}

		/**
		 * Is this flag a ship-slot
		 * @return boolean
		 */
		function isShipFitted()
		{
			if (strpos(strtolower($this->name), "loslot") !== false)
				return true;
			if (strpos(strtolower($this->name), "medslot") !== false)
				return true;
			if (strpos(strtolower($this->name), "hislot") !== false)
				return true;
			if (strpos(strtolower($this->name), "rigslot") !== false)
				return true;
			if (strpos(strtolower($this->name), "subsystem") !== false)
				return true;
			if (strpos(strtolower($this->name), "implant") !== false)
				return true;

			return false;
		}
	}
}
?>