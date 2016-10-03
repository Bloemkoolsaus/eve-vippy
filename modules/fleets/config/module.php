<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "Fleets";
$config["public"] = true;
$config["enabled"] = true;

/*
$config["submenu"][] = [
    "type" => "link",
    "name"	=> "Fleet Overview",
    "url"  => "fleets/fleet"
];
$config["submenu"][] = [
    "type" => "link",
    "name"	=> "Add Fleet",
    "url"  => "fleets/fleet/add"
];
*/

// SET CONFIG
foreach ($config as $var => $val) {
    \AppRoot::config("fleets".$var, $val);
}