<?php
namespace evescout\console
{
	class Import
	{
		function importConnections($systemName)
		{
			$system = \eve\model\SolarSystem::getSolarsystemByName($systemName);
			\MySQL::getDB()->delete("eve_scout", array("fromsystemid" => $system->id));
			\MySQL::getDB()->delete("eve_scout", array("tosystemid" => $system->id));

			// Chains met dit als home system legen
			foreach (\scanning\model\Chain::getChainsByHomesystem($system->id) as $chain) {
				$chain->clearChain(true);
			}

			$api = new \api\Client();
			$api->baseURL = "http://www.eve-scout.com/api/";
			$api->asArray = false;
			$result = $api->get("wormholes?limit=1000");

			foreach ($result["result"] as $sigdata)
			{
				$evescout = new \evescout\model\EveScout();
				$evescout->fromSystemID = $sigdata->solarSystemId;
				$evescout->toSystemID = $sigdata->wormholeDestinationSolarSystemId;
				$evescout->fromSignature = $sigdata->signatureId;
				$evescout->toSignature = $sigdata->wormholeDestinationSignatureId;
				$evescout->updatedate = date("Y-m-d H:i:s");
				$evescout->store();

				foreach (\scanning\model\Wormhole::getWormholesBySolarsystemID($evescout->fromSystemID) as $wormhole) {
					$wormhole->getChain()->addWormholeSystem($evescout->fromSystemID, $evescout->toSystemID);
				}
				foreach (\scanning\model\Wormhole::getWormholesBySolarsystemID($evescout->toSystemID) as $wormhole) {
					$wormhole->getChain()->addWormholeSystem($evescout->toSystemID, $evescout->fromSystemID);
				}
			}
		}
	}
}
?>