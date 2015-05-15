<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "Notifications";
$config["public"] = false;
$config["enabled"] = false;

// SET CONFIG
foreach ($config as $var => $val) {
	AppRoot::config("notices".$var, $val);
}
?>