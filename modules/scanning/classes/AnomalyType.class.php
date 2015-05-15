<?php 
namespace scanning
{
	class AnomalyType
	{
		public $id;
		public $name;
		public $type;
		
		private $db = null;
		
		function __construct($id=false)
		{
			$this->db = \MySQL::getDB();
			if ($id) {
				$this->id = $id;
				$this->load();
			}
		}

		function load($result=false)
		{
			if (!$result)
				$result = $this->db->getRow("SELECT * FROM mapanomalies_types WHERE id = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["id"];
				$this->name = $result["name"];
				$this->type = $result["type"];
			}
		}

		function store()
		{
			$data = array("name" => $this->name, "type" => $this->type);
			if ($this->id != 0)
				$data["id"] = $this->id;

			$result = $this->db->updateinsert("mapanomalies_types", $data, array("id" => $this->id));
			if ($this->id == 0)
				$this->id = $result;
		}


		/*** STATICS ***/

		public static function getAnomalyIdByName($name)
		{
			$db = \MySQL::getDB();
			if ($result = $db->getRow("SELECT id FROM mapanomalies_types WHERE name = ?", array($name)))
				return $result["id"];
			else
				return false;
		}
	}
}
?>