<?php
namespace users\view;

class Groups
{
    function getOverview($arguments=[])
    {
        if (!\User::getUSER()->isAdmin())
            \AppRoot::redirect("");

        $groups = \users\model\UserGroup::findAll(["authgroupid" => \User::getUSER()->getCurrentAuthGroupID()]);

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("groups", $groups);
        return $tpl->fetch("users/group/overview");
    }

    function getNew($arguments=[])
    {
        return $this->getEdit($arguments);
    }

    function getEdit($arguments=[])
    {
        if (!\User::getUSER()->isAdmin())
            \AppRoot::redirect("");

        $usergroup = new \users\model\UserGroup(array_shift($arguments));
        $permissions = [];
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
            else {
                $usergroup->authGroupID = null;
                $authgroups = \User::getUSER()->getAuthGroupsIDs();
                if (!\User::getUSER()->getIsSysAdmin())
                    $usergroup->authGroupID = $authgroups[0];
            }

            $usergroup->clearRights();
            if (\Tools::POST("roles")) {
                foreach ($_POST["roles"] as $role => $value) {
                    if ($role == "admin")
                        $usergroup->addRight("admin", "admin", "Vippy Admin");
                }
            }

            $usergroup->store();
            \AppRoot::redirect("users/groups");
        }

        if (\Tools::REQUEST("deleteuser"))
        {
            $user = new \users\model\User(\Tools::REQUEST("deleteuser"));
            $user->removeUserGroup($usergroup->id);
            $user->store();
            \AppRoot::redirect("/users/groups/edit/".$usergroup->id);
        }

        if (\Tools::POST("adduser"))
        {
            $user = new \users\model\User(\Tools::POST("adduser"));
            $user->addUserGroup($usergroup->id);
            $user->store();
            \AppRoot::redirect("/users/groups/edit/".$usergroup->id);
        }

        if (!$usergroup->getAuthgroup()) {
            $authgroups = \User::getUSER()->getAuthGroups();
            $usergroup->authGroupID = current($authgroups)->id;
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("usergroup", $usergroup);
        $tpl->assign("permissions", $permissions);
        $tpl->assign("accessgroups", \admin\model\AuthGroup::getAuthGroups());
        return $tpl->fetch("users/group/edit");
    }

    private function getOverviewSection()
    {
        $section = new \Section("user_groups","id");

        if (\User::getUSER()->getIsSysAdmin())
            $section->addElement("Access Group", "authgroupid", false, '\\admin\\elements\\AuthGroup\\AuthGroup');

        $section->addElement("UserGroup", "name");
        $section->allowEdit = true;
        $section->allowNew = true;

        if (!\User::getUSER()->getIsSysAdmin())
            $section->whereQuery = " WHERE authgroupid IN (".implode(",",\User::getUSER()->getAuthGroupsIDs()).")";

        return $section;
    }

}