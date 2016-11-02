<?php
namespace eve\model
{
	class Alliance
	{
		public $id = 0;
		public $ticker;
		public $name;

		private $corporations = null;

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
				$result = \MySQL::getDB()->getRow("SELECT * FROM alliances WHERE id = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["id"];
				$this->name = $result["name"];
			}
		}

		function store()
		{
			if ($this->id == 0)
				return false;

			$data = array("name" => $this->name);
			if ($this->id != 0)
				$data["id"] = $this->id;

			\MySQL::getDB()->updateinsert("alliances", $data, array("id" => $this->id));
            return true;
		}

		/**
		 * Get corporations
         * @return \eve\model\Corporation[]
		 */
		function getCorporations()
		{
			if ($this->corporations == null)
				$this->corporations = \eve\model\Corporation::getCorporationsByAlliance($this->id);

			return $this->corporations;
		}
	}
}
?>