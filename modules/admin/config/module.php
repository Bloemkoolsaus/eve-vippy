<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "Admin";
$config["public"] = true;
$config["enabled"] = true;
$config["sortorder"] = 1;

if (\User::getUSER() && !\Tools::REQUEST("ajax"))
{
    if (\User::getUSER()->isAdmin())
    {
        $config["submenu"][] = ["type" => "link", "name" => "Manage Maps", "url" => "admin/chain"];
        $config["submenu"][] = ["type" => "link", "name" => "Known Systems", "url" => "admin/knownwormholes"];
        $config["submenu"][] = ["type" => "link", "name" => "Notifications", "url" => "notices/notes"];
        $config["submenu"][] = ["type" => "seperator"];
        $config["submenu"][] = ["type" => "link", "name" => "Access Lists", "url" => "admin/accesslist"];
        $config["submenu"][] = ["type" => "link", "name" => "Access Control Group", "url" => "admin/authgroup/"];
    }

    if (\User::getUSER()->hasRight("users", "manageusers") || \User::getUSER()->isAdmin())
    {
        $config["submenu"][] = ["type" => "link", "name" => "Users", "url" => "users/user"];
        $config["submenu"][] = ["type" => "link", "name" => "User-Groups", "url" => "users/groups"];
        $config["submenu"][] = ["type" => "link", "name" => "Logs", "url" => "users/log"];
    }

    // System admin
    if (\User::getUSER()->getIsSysAdmin())
    {
        $config["submenu"][] = ["type" => "seperator"];
        $config["submenu"][] = ["type" => "link", "name" => "Authorization Groups", "url" => "admin/authgroup/"];
        $config["submenu"][] = ["type" => "link", "name" => "Pending Payments", "url" => "admin/payments/"];
    }
}

$config["rights"]["sysadmin"] = [
    "title" => "System Administrator",
    "name" => "sysadmin",
    "description" => "<p><b>!ATTENTION! This user has all rights and can view all scanning-chains!!</b></p>"
];

// SET CONFIG
foreach ($config as $var => $val) {
    \AppRoot::config("admin".$var, $val);
}