<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "Map";
$config["public"] = true;
$config["enabled"] = true;

// Haal beschikbare chains
foreach (\User::getUSER()->getAvailibleChains() as $chain)
{
    $config["submenu"][] = array("type" => "link",
                                 "name"	=> $chain->name,
                                 "url"  => "map/".$chain->name);
}

// SET CONFIG
foreach ($config as $var => $val) {
    \AppRoot::config("map".$var, $val);
}
?>