<?php
namespace eve\model
{
	class Corporation
	{
		public $id = 0;
		public $ticker;
		public $name;
		public $ceoID = 0;
		public $allianceID = 0;
		public $updateDate = null;

		private $alliance = null;

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
				$result = \MySQL::getDB()->getRow("SELECT * FROM corporations WHERE id = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["id"];
				$this->ticker = $result["ticker"];
				$this->name = $result["name"];
				$this->ceoID = $result["ceo"]-0;
				$this->allianceID = $result["allianceid"]-0;
				$this->updateDate = $result["updatedate"];
			}
		}

		function store()
		{
			if ($this->id == 0)
				return false;

			$this->updateDate = date("Y-m-d H:i:s");

			$data = array(	"id" => $this->id,
							"ticker" => $this->ticker,
							"name" => $this->name,
							"ceo" => $this->ceoID,
							"allianceid" => $this->allianceID,
							"updatedate" => $this->updateDate);
			\MySQL::getDB()->updateinsert("corporations", $data, array("id" => $this->id));

			// Update CEO
			\MySQL::getDB()->doQuery("UPDATE characters SET isceo = 0 WHERE corpid = ?", array($this->id));
			\MySQL::getDB()->doQuery("UPDATE characters SET isceo = 1 WHERE id = ?", array($this->ceoID));

            return true;
		}

		/**
		 * Get alliance.
		 * @return \eve\model\Alliance|null
		 */
		function getAlliance()
		{
			if ($this->alliance == null && $this->allianceID > 0)
				$this->alliance = new \eve\model\Alliance($this->allianceID);

			return $this->alliance;
		}







		/**
		 * Get corporation by id
		 * @param integer $corporationID
		 * @return \eve\model\Corporation|NULL
		 */
		public static function getCorporationByID($corporationID)
		{
			if ($result = \MySQL::getDB()->getRow("SELECT * FROM corporations WHERE id = ?", array($corporationID)))
			{
				$corp = new \eve\model\Corporation();
				$corp->load($result);
				return $corp;
			}
			return null;
		}

		/**
		 * Get corporations by alliance
		 * @param integer $allianceID
		 * @return \eve\model\Corporation[]
		 */
		public static function getCorporationsByAlliance($allianceID)
		{
			$corporations = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM corporations
													WHERE allianceid = ? ORDER BY name"
									, array($allianceID)))
			{
				foreach ($results as $result)
				{
					$corp = new \eve\model\Corporation();
					$corp->load($result);
					$corporations[] = $corp;
				}
			}
			return $corporations;
		}
	}
}
?>