<?php
namespace users\controller
{
	class UserGroup
	{
		public function getOverviewSection()
		{
			$section = new \Section("user_groups","id");
			$section->addElement("UserGroup","name");
			$section->allowEdit = true;
			$section->deletedfield = "deleted";
			$section->whereQuery = "WHERE hidden = 0";
			return $section;
		}

		public function getUsergroups()
		{
			$groups = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM user_groups ORDER BY name"))
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
			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("usergroup",$usergroup);
			$tpl->assign("permissions", $permissions);
			return $tpl->fetch(\SmartyTools::getTemplateDir("users")."usergroup.html");
		}
	}
}
?>