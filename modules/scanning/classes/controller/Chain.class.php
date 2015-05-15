<?php
namespace scanning\controller
{
	class Chain
	{
		function getChains()
		{
			$chains = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM mapwormholechains ORDER BY prio, name"))
			{
				foreach ($results as $result)
				{
					$chain = new \scanning\model\Chain();
					$chain->load($result);
					$chains[] = $chain;
				}
			}
			return $chains;
		}

		function getExitsSortedBySystem($systemID, $chainID=null)
		{
			if ($chainID == null)
				$chainID = \User::getSelectedChain();

			$systems = array();
			$chain = new \scanning\model\Chain($chainID);

			foreach ($chain->getWormholes() as $wormhole)
			{
				if ($wormhole->getSolarsystem() !== null && !$wormhole->getSolarsystem()->isWSpace())
				{
					$system = array("wormhole" 		=> $wormhole,
									"system" 		=> $wormhole->getSolarsystem(),
									"distance"		=> $wormhole->getSolarsystem()->getNrJumpsTo($systemID));
					$systems[$system["distance"]][] = $system;
				}
			}

			ksort($systems);
			return $systems;
		}
	}
}
?>