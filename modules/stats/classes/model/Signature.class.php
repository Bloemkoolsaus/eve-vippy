<?php
namespace stats\model
{
	class Signature
	{
		public $id = 0;
		public $userID;
		public $corporationID;
		public $signatureID;
		public $chainID;
		public $scandate;

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
				$result = \MySQL::getDB()->getRow("SELECT * FROM stats_signatures WHERE id = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["id"];
				$this->userID = $result["userid"];
				$this->corporationID = $result["corpid"];
				$this->signatureID = $result["sigid"];
				$this->chainID = $result["chainid"];
				$this->scandate = $result["scandate"];
			}
		}

		function store()
		{
			$data = array(	"userid"	=> $this->userID,
							"corpid" 	=> $this->corporationID,
							"sigid"		=> $this->signatureID,
							"chainid"	=> $this->chainID,
							"scandate"	=> date("Y-m-d H:i:s", strtotime($this->scandate)));

			if ($this->id != 0)
				$data["id"] = $this->id;

			$result = \MySQL::getDB()->updateinsert("stats_signatures", $data, array("id" => $this->id));
			if ($this->id == 0)
				$this->id = $result;
		}
	}
}
?>