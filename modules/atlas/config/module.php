<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "Atlas";
$config["public"] = false;
$config["enabled"] = true;

$config["submenu"][] = array("type" => "link",
							"name"	=> "Atlas",
							"newwindow" => 1);

// SET CONFIG
foreach ($config as $var => $val) {
	\AppRoot::config("atlas".$var, $val);
}
?>