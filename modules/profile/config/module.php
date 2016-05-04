<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "Profile";
$config["public"] = true;
$config["enabled"] = true;

$config["submenu"][] = array("type" => "link",
							"name"	=> "Account Settings");
$config["submenu"][] = array("type" => "link",
							"name"	=> "Characters",
							"section" => "chars");
$config["submenu"][] = array("type" => "link",
							"name"	=> "API Keys",
							"section" => "api");

$config["submenu"][] = array("type" => "seperator");
$config["submenu"][] = array("type" => "link",
							"name"	=> "My Capital-ships",
							"url" => "profile/capitals/");

// SET CONFIG
foreach ($config as $var => $val) {
	AppRoot::config("profile".$var, $val);
}
?>