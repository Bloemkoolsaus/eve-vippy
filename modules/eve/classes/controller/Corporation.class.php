<?php
namespace eve\controller
{
	class Corporation
	{
		/**
		 * Get corporation by name
		 * @param string $name
		 * @return \eve\model\Corporation
		 */
		function getCorporationByName($name)
		{
			$corporation = new \eve\model\Corporation();
			if ($result = \MySQL::getDB()->getRow("SELECT * FROM corporations WHERE name = ?", array($name)))
				$corporation->load($result);

			return $corporation;
		}

        /**
         * Import Corporation
         * @param $corporationID
         * @return \eve\model\Corporation|null
         */
		function importCorporation($corporationID)
		{
			$api = new \eve\controller\API();
			$api->setParam("corporationID", $corporationID);
			$result = $api->call("/corp/CorporationSheet.xml.aspx");

			if ($errors = $api->getErrors())
				return null;

            $corporation = new \eve\model\Corporation($corporationID);
            $corporation->id = (string)$result->result->corporationID;
            $corporation->name = (string)$result->result->corporationName;
            $corporation->ticker = (string)$result->result->ticker;
            $corporation->allianceID = (string)$result->result->allianceID;
            $corporation->ceoID = (string)$result->result->ceoID;
            $corporation->store();

            $alliance = new \eve\model\Alliance($corporation->allianceID);
            $alliance->id = (string)$result->result->allianceID;
            $alliance->name = (string)$result->result->allianceName;
            $alliance->store();

            return $corporation;
		}

		function corporationExists($corporationID)
		{
			if ($result = \MySQL::getDB()->getRow("SELECT COUNT(id) AS nr FROM corporations WHERE id = ?", array($corporationID)))
			{
				if ($result["nr"] > 0)
					return true;
			}
			return false;
		}
	}
}
?>