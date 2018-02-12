<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "Help";
$config["public"] = true;
$config["enabled"] = true;
$config["sortorder"] = 98;


$config["submenu"][] = [
    "type" => "link",
    "name" => "Map",
    "url" => "help/map"
];
$config["submenu"][] = [
    "type" => "link",
    "name" => "ESI",
    "url" => "help/esi"
];
$config["submenu"][] = [
    "type" => "link",
    "name" => "Subscription",
    "url" => "help/subscription"
];
$config["submenu"][] = [
    "type" => "link",
    "name" => "Contact",
    "url" => "help/contact"
];

// SET CONFIG
foreach ($config as $var => $val) {
	\AppRoot::config("help".$var, $val);
}