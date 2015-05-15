<?php
namespace profile\console
{
	class Profile
	{
		function checkApiKeys()
		{
			\AppRoot::setMaxExecTime(600);
			\AppRoot::debug("check api keys");

			/**
			 * API-Log opruimen.
			 */
			\MySQL::getDB()->doQuery("DELETE FROM api_log WHERE cachedate < ?", array(date("Y-m-d H:i:s")));


			/**
			 * Check keys
			 */

			$checkdate = date("Y-m-d H:i:s", mktime(date("H")-3,date("i"),date("s"),
													date("m"),date("d"),date("Y")));

			// Eerst keys verzamelen.
			$apiKeys = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM api_keys
													WHERE 	valid = 1 AND deleted = 0
													AND 	lastcheckdate < ?
													ORDER BY lastcheckdate ASC LIMIT 10"
										, array($checkdate)))
			{
				foreach ($results as $result)
				{
					$api = new \eve\model\API();
					$api->load($result);
					$apiKeys[] = $api;
				}
			}

			// We hebben een aantal keys die we gaan checken.
			foreach ($apiKeys as $key) {
				$key->validate();
			}

			// Characters resetten die geen geldige api (meer) heben
			\MySQL::getDB()->doQuery("	UPDATE	characters c
											INNER JOIN api_keys a on a.keyid = c.api_keyid
										SET		c.api_keyid = 0
										WHERE	a.valid = 0 OR a.deleted > 0");

			return true;
		}

		function checkCharacters()
		{
			$checkdate = date("Y-m-d H:i:s", mktime(date("H")-12,date("i"),date("s"),
													date("m"),date("d")-1,date("Y")));
			$characters = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM characters
													WHERE 	updatedate < ?
													ORDER BY updatedate ASC LIMIT 10"
										, array($checkdate)))
			{
				foreach ($results as $result)
				{
					$char = new \eve\model\Character();
					$char->load($result);
					$characters[] = $char;
				}
			}

			$controller = new \eve\controller\Character();
			foreach ($characters as $char) {
				$controller->importCharacter($char->id);
			}

			return true;
		}

		function checkCorporations()
		{
			// Verwijder character corporations.. vage shit..
			\MySQL::getDB()->doQuery("DELETE FROM corporations WHERE id IN (SELECT id FROM characters)");

			$controller = new \eve\controller\Corporation();
			$checkdate = date("Y-m-d H:i:s", mktime(date("H")-6,date("i"),date("s"),
													date("m"),date("d"),date("Y")));
			$corporations = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM corporations
													WHERE updatedate < ? OR updatedate is null
													ORDER BY updatedate ASC LIMIT 10"
											, array($checkdate)))
			{
				foreach ($results as $result)
				{
					$corp = new \eve\model\Corporation();
					$corp->load($result);
					$corporations[] = $corp;
				}
			}

			foreach ($corporations as $corp)
			{
				$controller->importCorporation($corp->id);

				// updatedate zetten, ook als het mislukt.
				$corp->load();
				$corp->store();
			}

			return true;
		}
	}
}
?>