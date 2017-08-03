<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "WH-Info";
$config["public"] = true;
$config["enabled"] = (\User::getUSER() && \User::getUSER()->isAuthorized())?true:false;

$config["submenu"][] = ["type" => "link", "name" => "WH Colors", "section" => "colors"];
$config["submenu"][] = ["type" => "link", "name" => "WH Mass Table", "section" => "mass"];
$config["submenu"][] = ["type" => "link", "name" => "System Effects", "section" => "effects"];
$config["submenu"][] = ["type" => "link", "name" => "Shattered Systems", "section" => "shattered"];

// SET CONFIG
foreach ($config as $var => $val) {
	\AppRoot::config("whinfo".$var, $val);
}