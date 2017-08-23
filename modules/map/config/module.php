<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "Map";
$config["public"] = true;
$config["enabled"] = true;
$config["sortorder"] = 2;

// Haal beschikbare chains
\AppRoot::debug("Start building maps menu ------------------------------------------");
if (\User::getUSER() && !\Tools::REQUEST("ajax")) {
    foreach (\User::getUSER()->getAvailibleChains(false) as $chain) {
        $config["submenu"][] = array(
            "type" => "link",
            "name"	=> $chain->name,
            "url"  => "map/".$chain->getURL()
        );
    }
}
\AppRoot::debug("End building maps menu ------------------------------------------");

// SET CONFIG
foreach ($config as $var => $val) {
    \AppRoot::config("map".$var, $val);
}