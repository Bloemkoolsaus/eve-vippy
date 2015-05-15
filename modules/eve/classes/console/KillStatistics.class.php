<?php
namespace eve\console
{
	class KillStatistics
	{
		function import()
		{
			if ($killstatsDate = \AppRoot::getDBConfig("cron_killstats"))
			{
				// Te kort geleden. Afbreken.
				if (strtotime("now") <= strtotime($killstatsDate))
					return false;
			}


			/**
			 * Oude data opruimen
			 */
			$cleanUpDate = date("Y-m-d", mktime(0,0,0,date("m")-1,date("d"),date("Y")))." 00:00:00";
			\MySQL::getDB()->doQuery("DELETE FROM mapsolarsystem_kill_statistics WHERE statdate < ?", array($cleanUpDate));


			/**
			 * Nieuwe data ophalen
			 */
			$api = new \eve\controller\API();
			$result = $api->call("/map/Kills.xml.aspx");

			if (!$errors = $api->getErrors())
			{
				$solarSystemIDs = array();
				foreach ($result->result->rowset->row as $system)
				{
					$data = array(	"solarsystemid" => (string)$system["solarSystemID"],
									"statdate"		=> date("Y-m-d H:i:s",strtotime((string)$result->result->dataTime)),
									"evetime"		=> date("Y-m-d H:i:s",strtotime((string)$result->currentTime)),
									"servertime"	=> date("Y-m-d H:i:s"),
									"shipkills"		=> (string)$system["shipKills"],
									"npckills"		=> (string)$system["factionKills"],
									"podkills"		=> (string)$system["podKills"]);
					$where = array(	"solarsystemid" => (string)$system["solarSystemID"],
									"statdate"		=> date("Y-m-d H:i:s",strtotime((string)$result->result->dataTime)));
					\MySQL::getDB()->updateinsert("mapsolarsystem_kill_statistics", $data, $where);

					if (!in_array((string)$system["solarSystemID"],$solarSystemIDs))
						$solarSystemIDs[] = (string)$system["solarSystemID"];
				}

				foreach ($solarSystemIDs as $systemID)
				{
					// Hoeven niet te laden, dus ID na aanroep zetten.
					$system = new \eve\model\SolarSystem();
					$system->id = $systemID;
					$system->setRecentKills();
				}

				$cachedTime = strtotime($api->getCachedUntill());
				$cachedTime = date("Y-m-d H:i:s", mktime(date("H",$cachedTime), date("i",$cachedTime)+1, date("s",$cachedTime),
														 date("m",$cachedTime), date("d",$cachedTime), date("Y",$cachedTime)));
				\AppRoot::setDBConfig("cron_killstats", $cachedTime);
				\AppRoot::setDBConfig("cron_killstats_last", date("Y-m-d H:i:s"));
			}

			return true;
		}
	}
}
?>