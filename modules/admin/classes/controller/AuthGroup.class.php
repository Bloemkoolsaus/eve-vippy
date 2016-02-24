<?php
namespace admin\controller
{
	class AuthGroup
	{
		/**
		 * Get overview section
		 * @return \Section|null
		 */
		function getOverviewSection()
		{
			if (count(\User::getUSER()->getAuthGroupsAdmins()) > 0 || \User::getUSER()->getIsSysAdmin())
			{
				$section = new \Section("user_auth_groups","id");
				$section->addElement("Access Control Group","name");
				$section->addElement("Allowed","allowed","id",'\\admin\\elements\\AuthGroup\\Allowed');

				if (\User::getUSER()->getIsSysAdmin())
				{
					$section->addElement("Users", "users", "id", '\\admin\\elements\\AuthGroup\\ValidUsers');
					$section->addElement("License", "subscription", "id", '\\admin\\elements\\AuthGroup\\Subscription');
				}

				$section->addElement("Last Active","active","id",'\\admin\\elements\\AuthGroup\\Activity');
				$section->allowEdit = true;

				if (!\User::getUSER()->getIsSysAdmin() || !\Tools::REQUEST("admin"))
				{
					$section->allowNew = false;
					$section->allowDelete = false;
					$section->allowSearch = false;

					$groupIDs = array();
					foreach (\User::getUSER()->getAuthGroupsAdmins() as $group) {
						$groupIDs[] = $group->id;
					}
					$section->whereQuery = " WHERE id IN (".implode(",",$groupIDs).")";
				}

				return $section;
			}

			return null;
		}

		/**
		 * Get edit form
		 * @param integer $authGroupID
		 * @return string
		 */
		function getEditForm($authGroupID)
		{

		}

		/**
		 * Get corporations by authgroupID
		 * @param int $authGroupID
		 * @return multitype:\eve\model\Corporation
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
		 * @return multitype:\eve\model\Alliance
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