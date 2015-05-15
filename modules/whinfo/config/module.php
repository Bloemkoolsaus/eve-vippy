<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "WH-Info";
$config["public"] = true;
$config["enabled"] = (\User::getUSER()->isAuthorized()) ? true : false;

$config["submenu"][] = array("type" => "link",
							"name"	=> "WH Colors",
							"section" => "colors");
$config["submenu"][] = array("type" => "link",
							"name"	=> "WH Mass Table",
							"section" => "mass");
$config["submenu"][] = array("type" => "link",
							"name"	=> "System Effects",
							"section" => "effects");
$config["submenu"][] = array("type" => "link",
							"name"	=> "Shattered Systems",
							"section" => "shattered");

// SET CONFIG
foreach ($config as $var => $val) {
	\AppRoot::config("whinfo".$var, $val);
}
?>