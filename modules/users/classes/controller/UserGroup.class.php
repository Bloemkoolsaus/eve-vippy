<?php
namespace users\controller
{
	class UserGroup
	{
		public function getOverviewSection()
		{
			$section = new \Section("user_groups","id");

            if (\User::getUSER()->getIsSysAdmin())
                $section->addElement("Access Group", "authgroupid", false, '\\admin\\elements\\AuthGroup\\AuthGroup');

            $section->addElement("UserGroup", "name");
			$section->allowEdit = true;

            if (!\User::getUSER()->getIsSysAdmin())
                $section->whereQuery = " WHERE authgroupid IN (".implode(",",\User::getUSER()->getAuthGroupsIDs()).")";

			return $section;
		}

		public function getUsergroups($user=null)
		{
            $query = ["authgroupid is not null"];
			$groups = [];

            if ($user != null)
                $query[] = "authgroupid IN (".implode(",",$user->getAuthGroupsIDs()).")";


			if ($results = \MySQL::getDB()->getRows("SELECT * FROM user_groups WHERE ".implode(" AND ", $query)." ORDER BY name"))
			{
				foreach ($results as $result)
				{
					$group = new \users\model\UserGroup();
					$group->load($result);
					$groups[] = $group;
				}
			}
			return $groups;
		}

		function getEditForm($groupID)
		{
			$usergroup = new \users\model\UserGroup($groupID);
			$permissions = array();
			foreach (\Modules::getModules() as $module) {
				if ($module == "admin")
					continue;
				if ($rights = \AppRoot::config($module."rights"))
					$permissions[$module] = $rights;
			}


			if (\Tools::POST("save"))
			{
				$usergroup->name = \Tools::POST("name");

                if (\Tools::POST("authgroupid"))
                    $usergroup->authGroupID = \Tools::POST("authgroupid");
                else
                {
                    $usergroup->authGroupID = null;
                    $authgroups = \User::getUSER()->getAuthGroupsIDs();
                    if (!\User::getUSER()->getIsSysAdmin())
                        $usergroup->authGroupID = $authgroups[0];
                }

				$usergroup->clearRights();
				foreach ($permissions as $module => $rights)
				{
					foreach ($rights as $right)
					{
						if (\Tools::POST($module."_".$right["name"]))
							$usergroup->addRight($module, $right["name"], $right["title"]);
					}
				}

				$usergroup->store();
                \AppRoot::redirect("index.php?module=users&section=usergroups");
			}

            if (\Tools::REQUEST("deleteuser"))
            {
                $user = new \users\model\User(\Tools::REQUEST("deleteuser"));
                $user->removeUserGroup($usergroup->id);
                $user->store();
                \AppRoot::redirect("index.php?module=users&section=usergroups&action=edit&id=".$usergroup->id);
            }

            if (\Tools::POST("adduser"))
            {
                $user = new \users\model\User(\Tools::POST("adduser"));
                $user->addUserGroup($usergroup->id);
                $user->store();
                \AppRoot::redirect("index.php?module=users&section=usergroups&action=edit&id=".$usergroup->id);
            }

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("usergroup",$usergroup);
			$tpl->assign("permissions", $permissions);
            $tpl->assign("accessgroups", \admin\model\AuthGroup::getAuthGroups());
			return $tpl->fetch("users/group/edit");
		}
	}
}
?>