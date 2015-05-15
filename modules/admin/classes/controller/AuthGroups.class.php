<?php
namespace admin\controller
{
	class AuthGroups
	{
		public static $corporations = null;
		public static $alliances = null;

		/**
		 * Get authorized corporations
		 * @return multitype:\eve\model\Corporation
		 */
		function getAuthorizedCorporations()
		{
			if (self::$corporations === null)
			{
				self::$corporations = array();
				if ($results = \MySQL::getDB()->getRows("SELECT  c.*
														FROM    corporations c
														    INNER JOIN user_auth_groups_corporations ac ON ac.corporationid = c.id
														    INNER JOIN user_auth_groups a ON a.id = ac.authgroupid
														WHERE   '".date("Y-m-d")."' BETWEEN a.validfrom AND a.validtill
													UNION
														SELECT  c.*
														FROM    corporations c
														    INNER JOIN alliances a ON a.id = c.allianceid
														    INNER JOIN user_auth_groups_alliances agc ON agc.allianceid = a.id
														    INNER JOIN user_auth_groups ag ON ag.id = agc.authgroupid
														WHERE   '".date("Y-m-d")."' BETWEEN ag.validfrom AND ag.validtill"))
				{
					foreach ($results as $result)
					{
						$corp = new \eve\model\Corporation();
						$corp->load($result);
						self::$corporations[] = $corp;
					}
				}
			}

			return self::$corporations;
		}

		/**
		 * Get authorized alliances
		 * @return multitype:\eve\model\Alliance
		 */
		function getAuthorizedAlliances()
		{
			if (self::$alliances === null)
			{
				self::$alliances = array();
				if ($results = \MySQL::getDB()->getRows("SELECT *
														FROM    alliances c
														    INNER JOIN user_auth_groups_alliances agc ON agc.allianceid = c.id
														    INNER JOIN user_auth_groups ag ON ag.id = agc.authgroupid
														WHERE   ? BETWEEN ag.validfrom AND ag.validtill"
											, array(date("Y-m-d"))))
				{
					foreach ($results as $result)
					{
						$ally = new \eve\model\Alliance();
						$ally->load($result);
						self::$alliances[] = $ally;
					}
				}
			}

			return self::$alliances;
		}
	}
}
?>