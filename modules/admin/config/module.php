<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "Admin";
$config["public"] = true;
$config["enabled"] = true;


if (\User::getUSER()->isAdmin())
{
	$config["submenu"][] = array("type" => "link",
								"name"	=> "Manage Chains",
								"section" => "chains");
	$config["submenu"][] = array("type" => "link",
								"name"	=> "Known wormholes",
								"section" => "knownwormholes");
	$config["submenu"][] = array("type" => "link",
								"name"	=> "Notifications",
								"module"=> "notices");
	$config["submenu"][] = array("type" => "seperator");
}

if (count(\User::getUSER()->getAuthGroupsAdmins()) > 0)
{
	$config["submenu"][] = array("type" => "link",
								"name"	=> "Access Control Group",
								"module" => "admin",
								"section" => "authgroups");
}
// USERS
if (\User::getUSER()->hasRight("users", "manageusers") || \User::getUSER()->isAdmin())
{
	$config["submenu"][] = array("type" => "link",
								"name"	=> "Users",
								"module"=> "users");

    if (\User::getUSER()->isAdmin() || \User::getUSER()->hasRight("users", "manageusers"))
    {
        $config["submenu"][] = array("type" => "link",
                                     "name"	=> "User-Groups",
                                     "module" => "users",
                                     "section" => "usergroups");
    }

	$config["submenu"][] = array("type" => "link",
								"name"	=> "Logs",
								"module"=> "users",
								"section"=> "logs");
}

// System admin
if (\User::getUSER()->getIsSysAdmin())
{
	$config["submenu"][] = array("type" => "seperator");
	$config["submenu"][] = array("type" => "link",
								"name"	=> "Authorization Groups",
								"section" => "authgroups",
								"admin" => 1);
	$config["submenu"][] = array("type" => "link",
								"name"	=> "Subscriptions",
								"section" => "subscriptions");
	$config["submenu"][] = array("type" => "link",
								"name"	=> "Clear Cache",
								"section" => "clearcache");

}


$config["rights"]["sysadmin"] = array("title" => "System Administrator", "name" => "sysadmin",
									"description" => "<p><b>!ATTENTION! This user has all rights and can view all scanning-chains!!</b></p>");

// SET CONFIG
foreach ($config as $var => $val) {
	\AppRoot::config("admin".$var, $val);
}
?>