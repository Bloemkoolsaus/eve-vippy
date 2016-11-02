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
            $corporation = new \eve\model\Corporation($corporationID);
            \AppRoot::doCliOutput("Import Corporation ".$corporation->name);

            // Alleen udpaten indien ouder dan 20 uur
            if (strtotime($corporation->updateDate) <= strtotime("now")-72000)
            {
                $api = new \eve\controller\API();
                $api->setParam("corporationID", $corporationID);
                $result = $api->call("/corp/CorporationSheet.xml.aspx");

                if ($errors = $api->getErrors())
                    return null;

                $corporation->id = (string)$result->result->corporationID;
                $corporation->name = (string)$result->result->corporationName;
                $corporation->ticker = (string)$result->result->ticker;
                $corporation->allianceID = (int)$result->result->allianceID;
                $corporation->ceoID = (int)$result->result->ceoID;
                $corporation->store();

                if ((int)$corporation->allianceID) {
                    $alliance = new \eve\model\Alliance((string)$corporation->allianceID);
                    $alliance->id = (string)$result->result->allianceID;
                    $alliance->name = (string)$result->result->allianceName;
                    $alliance->store();
                }
            }

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