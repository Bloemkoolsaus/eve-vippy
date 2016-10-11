<?php
foreach (\admin\model\AuthGroup::getAuthGroups() as $group)
{
    if ($group->getConfig("dir_admin_disabled"))
        continue;

    $adminUserGroup = null;
    foreach ($group->getUsergroups() as $usergroup) {
        if ($usergroup->hasRight("admin", "admin"))
            $adminUserGroup = $usergroup;
    }
    if (!$adminUserGroup)
    {
        $adminUserGroup = new \users\model\UserGroup();
        $adminUserGroup->name = "VIPPY Admin";
        $adminUserGroup->authGroupID = $group->id;
        $adminUserGroup->store();

        $adminUserGroup->addRight("admin", "admin", 1);
        $adminUserGroup->store();
    }

    foreach ($group->getAllowedCorporations() as $corp)
    {
        if ($results = \MySQL::getDB()->getRows("select * from characters where corpid = ?", [$corp->id]))
        {
            foreach ($results as $result)
            {
                $char = new \eve\model\Character();
                $char->load($result);
                if ($char->isDirector()) {
                    if ($char->getUser()) {
                        $user = $char->getUser();
                        $user->addUserGroup($adminUserGroup->id);
                        $user->store();
                    }
                }
            }
        }
    }
}