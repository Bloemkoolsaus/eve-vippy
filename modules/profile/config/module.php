<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "Profile";
$config["public"] = true;
$config["enabled"] = true;
$config["sortorder"] = 1;

$config["submenu"][] = [
    "type"  => "link",
    "name"  => "Account",
    "url"   => "profile/account"
];
$config["submenu"][] = [
    "type"  => "link",
    "name"  => "Capital-ships",
    "url"   => "profile/capitals"
];

// SET CONFIG
foreach ($config as $var => $val) {
	\AppRoot::config("profile".$var, $val);
}