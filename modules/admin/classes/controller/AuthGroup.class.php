<?php
namespace admin\controller
{
	class AuthGroup
	{
		/**
		 * Get corporations by authgroupID
		 * @param int $authGroupID
		 * @return \eve\model\Corporation[]
		 */
		function getCorporationsByAuthGroupID($authGroupID)
		{
			$corporations = array();
			if ($results = \MySQL::getDB()->getRows("SELECT  c.*
													FROM    corporations c
													    INNER JOIN user_auth_groups_corporations a ON a.corporationid = c.id
													WHERE    a.authgroupid = ?
												UNION
													SELECT  c.*
													FROM    corporations c
													    INNER JOIN alliances a ON a.id = c.allianceid
													    INNER JOIN user_auth_groups_alliances ga ON ga.allianceid = a.id
													WHERE    ga.authgroupid = ?
												ORDER BY name"
									, array($authGroupID,$authGroupID)))
			{
				foreach ($results as $result)
				{
					$corp = new \eve\model\Corporation();
					$corp->load($result);
					$corporations[] = $corp;
				}
			}

			return $corporations;
		}

		/**
		 * Get alliances by authgroupID
		 * @param int $authGroupID
		 * @return \eve\model\Alliance[]
		 */
		function getAlliancesByAuthGroupID($authGroupID)
		{
			$alliances = array();
			if ($results = \MySQL::getDB()->getRows("SELECT	c.*
													FROM	alliances c
														INNER JOIN user_auth_groups_alliances a ON a.allianceid = c.id
													WHERE	a.authgroupid = ?
													ORDER BY c.name"
											, array($authGroupID)))
			{
				foreach ($results as $result)
				{
					$ally = new \eve\model\Alliance();
					$ally->load($result);
					$alliances[] = $ally;
				}
			}
			return $alliances;
		}
	}
}
?>