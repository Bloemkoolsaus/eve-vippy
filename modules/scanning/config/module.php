<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "Scanning";
$config["public"] = true;
$config["enabled"] = true;

// Haal beschikbare chains
foreach (\User::getUSER()->getAvailibleChains() as $chain)
{
	$config["submenu"][] = array("type" => "link",
								"name"	=> $chain->name,
								"section" => "overview",
								"chain"	=> $chain->id);
}

// SET CONFIG
foreach ($config as $var => $val) {
	\AppRoot::config("scanning".$var, $val);
}
?>