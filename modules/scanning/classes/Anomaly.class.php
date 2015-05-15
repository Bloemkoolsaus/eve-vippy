<?php 
namespace scanning
{
	class Anomaly
	{
		public $id;
		public $chainID;
		public $solarSystemID;
		public $anomalyID;
		public $signatureID;

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
				$result = $this->db->getRow("SELECT * FROM mapanomalies WHERE id = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["id"];
				$this->chainID = $result["chainid"];
				$this->solarSystemID = $result["solarsystemid"];
				$this->anomalyID = $result["anomalyid"];
				$this->signatureID = $result["signatureid"];
			}
		}

		function store()
		{
			$data = array(	"chainid" 		=> $this->chainID, 
							"solarsystemid" => $this->solarSystemID, 
							"anomalyid" 	=> $this->anomalyID, 
							"signatureid" 	=> $this->signatureID);
			if ($this->id != 0)
				$data["id"] = $this->id;

			$result = $this->db->updateinsert("mapanomalies", $data, array("id" => $this->id));
			if ($this->id == 0)
				$this->id = $result;
		}

		function delete()
		{
			$this->db->delete("mapanomalies", array("id" => $this->id));
		}


		/*** STATICS ***/

		public static function checkSignatureID($signature)
		{
			$db = \MySQL::getDB();
			
			if ($result = $db->getRow("	SELECT 	id
										FROM 	mapanomalies
										WHERE	signatureid = ?
										AND		chainid = ?
										AND		solarsystemid = ?"
					, array($signature,\User::getSelectedChain(),\User::getSelectedSystem())))
				return $result["id"];
			else
				return false;
		}

		public static function getSystemAnomalies($systemID,$chainID=false)
		{
			$db = \MySQL::getDB();
			$anomalies = array();

			if (!$chainID)
				$chainID = \User::getSelectedChain();

			if ($results = $db->getRows("SELECT	a.id, a.signatureid, t.name
										FROM    mapanomalies a
										    INNER JOIN mapanomalies_types t ON t.id = a.anomalyid
										WHERE 	a.chainid = ? AND a.solarsystemid = ?
										ORDER BY t.name, a.signatureid"
								, array($chainID, $systemID)))
			{
				foreach ($results as $result) {
					$anomalies[] = array("id" => $result["id"],
										"sig" => $result["signatureid"], 
										"name" => $result["name"]);
				}
			}

			return $anomalies;
		}

		public static function removeAnomaly($id)
		{
			$anom = new Anomaly($id);
			$anom->delete();
		}
	}
}
?>