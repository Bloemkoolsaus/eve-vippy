<?php
namespace eve\model
{
	class Region
	{
		public $id = 0;
		public $name;

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
				$result = \MySQL::getDB()->getRow("SELECT * FROM ".\eve\Module::eveDB().".mapregions WHERE regionid = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["regionid"];
				$this->name = $result["regionname"];
			}
		}
	}
}
?>