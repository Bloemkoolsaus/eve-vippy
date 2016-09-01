<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "Profile";
$config["public"] = true;
$config["enabled"] = true;

$config["submenu"][] = [
    "type"  => "link",
    "name"  => "Account Settings",
    "url"   => "profile/account"
];
$config["submenu"][] = [
    "type"  => "link",
    "name"	=> "Characters",
    "url"   => "profile/characters"
];
$config["submenu"][] = [
    "type"  => "link",
    "name"  => "My Capital-ships",
    "url"   => "profile/capitals"
];

// SET CONFIG
foreach ($config as $var => $val) {
	\AppRoot::config("profile".$var, $val);
}