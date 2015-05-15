<?php
namespace scanning\controller
{
	class Anomaly
	{
		function addAnomaly($name, $signature, $type)
		{
			\AppRoot::debug("addAnomaly($name,$signature,$type)");

			// Check eerst of de sig al bestaat.
			if ($existID = Anomaly::checkSignatureID($signature))
				$anomaly = new Anomaly($existID);
			else
				$anomaly = new Anomaly();

			// Check of de anomaly type al bestaat
			if (!$anomID = AnomalyType::getAnomalyIdByName($name))
			{
				$anom = new AnomalyType();
				$anom->name = $name;
				$anom->type = $type;
				$anom->store();
				$anomID = $anom->id;
			}

			$anomaly->solarSystemID = \User::getSelectedSystem();
			$anomaly->chainID = \User::getSelectedChain();
			$anomaly->anomalyID = $anomID;
			$anomaly->signatureID = $signature;
			$anomaly->store();

			return true;
		}

	}
}
?>