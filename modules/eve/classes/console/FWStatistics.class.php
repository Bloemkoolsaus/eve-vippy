<?php
namespace eve\console
{
	class FWStatistics
	{
		function import()
		{
			\AppRoot::setMaxExecTime(500);

			if ($fwstatsDate = \AppRoot::getDBConfig("cron_fwstats"))
			{
				// Te kort geleden. Afbreken.
				if (strottime("now") <= strtotime($fwstatsDate))
					return false;
			}

			$solarSystems = array();

			// Sovereignity ophalen
			$api = new \eve\controller\API();
			$result = $api->call("/map/Sovereignty.xml.aspx");

			if (!$errors = $api->getErrors())
			{
				foreach ($result->result->rowset->row as $system)
				{
					$systemID = (int)$system["solarSystemID"];
					$factionID = (int)$system["factionID"];
					$allianceID = (int)$system["allianceID"];

					$solarSystems[$systemID] = array("solarsystemid"=> $systemID,
													"allianceid"	=> $allianceID,
													"factionid"		=> $factionID,
													"fwsystem" 		=> 0,
													"contested" 	=> 0);
				}
			}

			// Faction warfare ophalen
			$api = new \eve\controller\API();
			$result = $api->call("/map/FacWarSystems.xml.aspx");

			if (!$errors = $api->getErrors())
			{
				foreach ($result->result->rowset->row as $system)
				{
					$systemID = (int)$system["solarSystemID"];
					$factionID = (int)$system["occupyingFactionID"];

					$solarSystems[$systemID]["fwsystem"] = 1;
					$solarSystems[$systemID]["contested"] = (strtolower((string)$system["contested"])=="true")?1:0;

					if ($factionID > 0)
						$solarSystems[$systemID]["factionid"] = $factionID;
				}
			}


			foreach ($solarSystems as $id => $system)
			{
				\MySQL::getDB()->insert("mapsolarsystem_sov_stats", $system, "solarsystemid");

				// Cache resetten. Alleen id nodig, hoeven niet het object in te laden.
				$solarsystem = new \eve\model\SolarSystem();
				$solarsystem->id = $systemID;
				$solarsystem->setSovStatistics($system);
			}


			/*
			$cachedTime = strtotime($api->getCachedUntill());
			$cachedTime = date("Y-m-d H:i:s", mktime(date("H",$cachedTime), date("i",$cachedTime)+1, date("s",$cachedTime),
					date("m",$cachedTime), date("d",$cachedTime), date("Y",$cachedTime)));
			\AppRoot::setDBConfig("cron_fwstats", $cachedTime);
			*/

			return true;
		}
	}
}
?>