<?php
namespace evescout\model
{
	class EveScout
	{
		public $fromSystemID;
		public $toSystemID;
		public $fromSignature;
		public $toSignature;
		public $updatedate;

		private $fromSolarSystem;
		private $toSolarSystem;

		function __construct($from=false, $to=false)
		{
			if ($from && $to) {
				$this->fromSystemID = $from;
				$this->toSystemID = $to;
				$this->load();
			}
		}

		function load($result=false)
		{
			if (!$result)
			{
				$result = \MySQL::getDB()->getRow("SELECT * FROM eve_scout WHERE fromsystemid = ? AND tosystemid = ?"
												, array($this->fromSystemID, $this->toSystemID));
			}

			if ($result)
			{
				$this->fromSystemID = $result["fromsystemid"];
				$this->fromSignature = $result["fromsignature"];
				$this->toSystemID = $result["tosystemid"];
				$this->toSignature = $result["tosignature"];
				$this->updatedate = $result["updatedate"];
			}
		}

		function store()
		{
			$data = array(	"fromsystemid"	=> $this->fromSystemID,
							"fromsignature"	=> $this->fromSignature,
							"tosystemid"	=> $this->toSystemID,
							"tosignature"	=> $this->toSignature,
							"updatedate"	=> $this->updatedate);
			$where = array(	"fromsystemid"	=> $this->fromSystemID,
							"tosystemid"	=> $this->toSystemID);
			\MySQL::getDB()->updateinsert("eve_scout", $data, $where);
		}
	}
}
?>