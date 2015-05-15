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
			$authgroup = new \admin\model\AuthGroup($authGroupID);
			$errors = array();

			if (!$authgroup->getMayAdmin(\User::getUSER()))
				return "";


			\AppRoot::title($authgroup->name);

			if (\Tools::REQUEST("deletealliance"))
			{
				$authgroup->removeAlliance(\Tools::REQUEST("deletealliance"));
				$authgroup->store();

				// Check of nog wel toegang heeft. Anders ongedaan maken.
				\User::getUSER()->resetAuthGroups();
				if (!$authgroup->getMayAdmin(\User::getUSER()))
				{
					// Oops!
					$authgroup->addAllianceById(\Tools::REQUEST("deletealliance"));
					$authgroup->store();

					$alliance = new \eve\model\Alliance(\Tools::REQUEST("deletealliance"));
					$errors[] = "<b>Cannot remove $alliance->name</b><br />That would revoke your own access to this group.";
				}
				else
					\AppRoot::redirect("index.php?module=admin&section=authgroups&action=edit&id=".$authgroup->id);
			}

			if (\Tools::REQUEST("deletecorp"))
			{
				$authgroup->removeCorporation(\Tools::REQUEST("deletecorp"));
				$authgroup->store();

				// Check of nog wel toegang heeft. Anders ongedaan maken.
				\User::getUSER()->resetAuthGroups();
				if (!$authgroup->getMayAdmin(\User::getUSER()))
				{
					// Oops!
					$authgroup->addCorporation(\Tools::REQUEST("deletecorp"));
					$authgroup->store();

					$corporation = new \eve\model\Corporation(\Tools::REQUEST("deletecorp"));
					$errors[] = "<b>Cannot remove $corporation->name</b><br />That would revoke your own access to this group.";
				}
				else
					\AppRoot::redirect("index.php?module=admin&section=authgroups&action=edit&id=".$authgroup->id);
			}


			if (\Tools::POST("id") || \Tools::POST("name"))
			{
				if (\Tools::POST("name"))
					$authgroup->name = \Tools::POST("name");

				$authgroup->mainChainID = \Tools::POST("mainchain");

				if (\Tools::POST("corporation"))
					$authgroup->addCorporationById(\Tools::POST("corporation"));

				if (\Tools::POST("alliance"))
					$authgroup->addAllianceById(\Tools::POST("alliance"));

				$authgroup->store();


				if (isset($_POST["subscription"]))
				{
					$subscription = new \admin\model\Subscription();
					$subscription->authgroupID = $authgroup->id;
					$subscription->description = $_POST["subscription"]["description"];
					$subscription->amount = $_POST["subscription"]["amount"];
					$subscription->fromdate = (isset($_POST["subscription"]["fromdate"]))?$_POST["subscription"]["fromdate"]:null;
					$subscription->tilldate = (isset($_POST["subscription"]["tilldate"]))?$_POST["subscription"]["tilldate"]:null;
					$subscription->store();
				}

				\AppRoot::redirect("index.php?module=admin&section=authgroups&action=edit&id=".$authgroup->id);
			}

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("errors", $errors);
			$tpl->assign("authgroup", $authgroup);

			$allianceEdit = new \eve\elements\Alliance("Alliance", "alliance");
			$tpl->assign("addAllianceElement", $allianceEdit);

			$corporationEdit = new \eve\elements\Corporation("Corporation", "corporation");
			$tpl->assign("addCorporationElement", $corporationEdit);

			if ($authgroup->getSubscription() != null)
			{
				// Get payment months
				$months = array();
				$curdate = $authgroup->getSubscription()->fromdate;
				while (date("Ym", strtotime($curdate)) <= date("Ym"))
				{
					$months[] = array("month" 	=> \Tools::getFullMonth(date("m",strtotime($curdate)))." ".date("Y",strtotime($curdate)),
									"due"		=> $authgroup->getSubscription()->getAmount(),
									"amount"	=> $authgroup->getSubscription()->getPayed($curdate));

					$curdate = date("Y-m-d", mktime(0,0,0,date("m",strtotime($curdate))+1,date("d",strtotime($curdate)),date("Y",strtotime($curdate))));
				}
				$tpl->assign("monthsdue", $months);
			}

			return $tpl->fetch("admin/authgroups/edit");
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