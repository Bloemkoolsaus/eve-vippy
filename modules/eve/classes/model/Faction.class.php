<?php
namespace eve\model
{
	class Faction
	{
		public $id = 0;
		public $name;
		public $description;

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
				$result = \MySQL::getDB()->getRow("SELECT * FROM ".\eve\Module::eveDB().".chrfactions WHERE factionid = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["factionid"];
				$this->name = $result["factionname"];
				$this->description = $result["description"];
			}
		}
	}
}
?>