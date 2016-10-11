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
    "name" => "Crest",
    "url" => "help/crest"
];

// SET CONFIG
foreach ($config as $var => $val) {
	\AppRoot::config("help".$var, $val);
}