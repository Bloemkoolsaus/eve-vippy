<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "Admin";
$config["public"] = true;
$config["enabled"] = true;
$config["sortorder"] = 1;

if (\User::getUSER())
{
    if (\User::getUSER()->isAdmin())
    {
        $config["submenu"][] = array("type" => "link",
                                    "name"	=> "Manage Maps",
                                    "url"   => "admin/chain");
        $config["submenu"][] = array("type" => "link",
                                    "name"	=> "Known Systems",
                                    "url" => "admin/knownwormholes");
        $config["submenu"][] = array("type" => "link",
                                    "name"	=> "Notifications",
                                    "url" => "notices/notes");
        $config["submenu"][] = array("type" => "seperator");
    }

    if (count(\User::getUSER()->getAuthGroupsAdmins()) > 0)
    {
        $config["submenu"][] = [
            "type" => "link",
            "name" => "Access Control Group",
            "url" => "admin/authgroup/edit/".\User::getUSER()->getCurrentAuthGroupID()
        ];
    }

    if (\User::getUSER()->hasRight("users", "manageusers") || \User::getUSER()->isAdmin())
    {
        $config["submenu"][] = array(
            "type" => "link",
            "name" => "Users",
            "url" => "users/user"
        );

        if (\User::getUSER()->isAdmin() || \User::getUSER()->hasRight("users", "manageusers"))
        {
            $config["submenu"][] = array(
                "type" => "link",
                "name" => "User-Groups",
                "url" => "users/groups"
            );
        }

        $config["submenu"][] = array(
            "type" => "link",
            "name"	=> "Logs",
            "url"=> "users/log"
        );
    }

    // System admin
    if (\User::getUSER()->getIsSysAdmin())
    {
        $config["submenu"][] = array("type" => "seperator");
        $config["submenu"][] = array("type" => "link",
            "name"	=> "Authorization Groups",
            "url"   => "admin/authgroup/");
        $config["submenu"][] = array("type" => "link",
            "name"	=> "Subscriptions",
            "section" => "subscriptions");
        $config["submenu"][] = array("type" => "link",
            "name"	=> "Clear Cache",
            "section" => "clearcache");
    }

    /*
    if (\User::getUSER()->isAdmin())
    {
        $config["submenu"][] = array(
            "type" => "link",
            "name" => "API Management",
            "url" => "api/admin");
    }
    */

    $config["rights"]["sysadmin"] = array(
        "title" => "System Administrator",
        "name" => "sysadmin",
        "description" => "<p><b>!ATTENTION! This user has all rights and can view all scanning-chains!!</b></p>"
    );
}

// SET CONFIG
foreach ($config as $var => $val) {
	\AppRoot::config("admin".$var, $val);
}