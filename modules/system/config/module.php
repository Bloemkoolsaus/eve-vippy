<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "fittings";
$config["public"] = false;
$config["enabled"] = false;

// SET CONFIG
foreach ($config as $var => $val) {
	\AppRoot::config("system".$var, $val);
}
?>