<?php
namespace users\controller
{
	class User
	{
		function getOverviewSection()
		{
			$section = new \Section("users", "id");
			$section->addElement("Name", "displayname")->inOverview = false;;
			$section->addElement("Name", "fullname", "id", '\\users\\elements\\User\\Name');
			$section->addElement("Corporation", "corp", "id", '\\users\\elements\\User\\Corporation');
			$section->addElement("Username", "username");
			$section->addElement("E-mail", "email");
			$section->addElement("Status", "status", "id", '\\users\\elements\\User\\Status');
			$section->addElement("Last Login", "uid", "id", '\\users\\elements\\User\\LastLogin');

			$section->orderBy = "displayname";
			$section->updatefield = "updatedate";

			$queryParams = array();


			if (!\User::getUSER()->hasRight("users", "manageusers"))
			{
				$allowedCorporationIDs = array();
				foreach (\User::getUSER()->getAuthGroups() as $group)
				{
					foreach ($group->getAllowedCorporations() as $corp)
					{
						if (\User::getUSER()->getIsCEO($corp->id))
						{
							// Mag heel de alliance zien.
							if ($corp->getAlliance() != null)
							{
								foreach ($corp->getAlliance()->getCorporations() as $acorp) {
									$allowedCorporationIDs[] = $acorp->id;
								}
							}
							else
								$allowedCorporationIDs[] = $corp->id;
						}
						else if (\User::getUSER()->getIsDirector($corp->id))
						{
							// Mag deze corp zien
							$allowedCorporationIDs[] = $corp->id;
						}
					}
				}

				if (count($allowedCorporationIDs) > 0)
					$queryParams[] = "id IN (SELECT userid FROM characters WHERE corpid IN (".implode(",",$allowedCorporationIDs)."))";
				else
					return false;
			}

            $section->allowEdit = true;
			$section->allowDelete = false;
			$section->allowNew = false;

			if (count($queryParams) > 0)
				$section->whereQuery = " WHERE ".implode(" AND ", $queryParams);

			if (\Tools::POST("searchusers"))
			{
				$searchQueryParams = array();
				$searchQueryParams[] = "id IN (	SELECT	c.userid
												FROM 	characters c
													INNER JOIN corporations corp ON corp.id = c.corpid
													LEFT JOIN alliances ally ON ally.id = corp.allianceid
												WHERE 	corp.name LIKE '%".\Tools::POST("searchusers")."%'
												OR 		ally.name LIKE '%".\Tools::POST("searchusers")."%')";

				if (count($searchQueryParams) > 0)
					$section->searchQuery = implode(" OR ", $searchQueryParams);

				$section->limit = 500;
			}

			return $section;
		}

		function getEditForm($userID)
		{
			if (!\User::getUSER()->getIsDirector())
				\AppRoot::redirect(APP_URL);

			$errors = array();
			$messages = array();
			$user = new \users\model\User($userID);

			if (!\User::getUSER()->getIsSysAdmin())
			{
				if ($user->getMainCorporationID() !== \User::getUSER()->getMainCorporationID())
					\AppRoot::redirect(APP_URL);
			}

			if (\Tools::POST("resetpassword"))
			{
				$password = \Tools::generateRandomString();

				$user->password = \User::generatePassword($password);
				$user->store();

				$messages[] = "The password of ".$user->displayname." has been reset";
				$messages[] = "<span style='font-weight: bold; font-size: 16px;'>The new password is: ".$password."</span>";
				$messages[] = "It is recommended that ".$user->displayname." changes his new password after login.";
			}

			if (\Tools::POST("banuser"))
			{
				$user->deleted = true;
				$user->store();
				\AppRoot::refresh();
			}
			if (\Tools::POST("unbanuser"))
			{
				$user->deleted = false;
				$user->store();
				\AppRoot::refresh();
			}

			if (\Tools::POST("saveusergroups"))
			{
				if (\User::getUSER()->hasRight("admin","sysadmin"))
				{
					$user->clearUserGroups();
					$usergroupController = new \users\controller\UserGroup();
					foreach ($usergroupController->getUsergroups() as $group)
					{
						if (\Tools::POST("group".$group->id))
							$user->addUserGroup($group->id);
					}
					$user->store();
				}
				\AppRoot::redirect("index.php?module=users&action=edit&id=".$user->id);
			}

			$characters = array();
			$charController = new \eve\controller\Character();

			foreach ($charController->getCharactersByUserID($user->id) as $char)
			{
				$corp = new \eve\model\Corporation($char->corporationID);
				$alliance = new \eve\model\Alliance($corp->allianceID);
				$character = array(	"id"	=> $char->id,
									"name"	=> $char->name,
									"corporationid"	=> $corp->id,
									"corpticker"	=> $corp->ticker,
									"corporation"	=> $corp->name,
									"allianceid"	=> $alliance->id,
									"alliance"		=> $alliance->name,
									"ceo"		=> $char->isCEO,
									"apikey"	=> $char->apiKeyID,
									"lastupdate"=> $char->updatedate,
									"director"	=> $char->isDirector,
									"title"		=> implode(", ",$char->titles),
									"updatedate" => \Tools::getFullDate($char->updatedate,true,false));

				if ($user->getMainCharacterID() == $char->id)
					$character["main"] = 1;

				$characters[] = $character;
			}

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("user", $user);
			$tpl->assign("chars", $characters);
			$tpl->assign("errors", $errors);
			$tpl->assign("messages", $messages);

			if (\User::getUSER()->getIsSysAdmin())
			{
				$tpl->assign("issysadmin",1);

				$usergroupController = new \users\controller\UserGroup();
				$tpl->assign("usergroups", $usergroupController->getUsergroups());
			}

			$lastLogin = $user->getLastLogin();
			if ($lastLogin)
			{
				$ipInfo = \Tools::getLocationByIP($lastLogin["ipaddress"]);
				$tpl->assign("lastlogindate", \Tools::getFullDate($lastLogin["logdate"],true,true));
				$tpl->assign("lastloginip", $ipInfo);
			}

			return $tpl->fetch("users/edit");
		}
	}
}
?>