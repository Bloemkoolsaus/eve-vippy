<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "Users";
$config["public"] = false;
$config["enabled"] = false;

// RIGHTS
$config["rights"]["manageusers"] = array("title" => "Admin Users", "name" => "manageusers");
$config["rights"]["managegroups"] = array("title" => "Admin Groups", "name" => "managegroups");

// SET CONFIG
foreach ($config as $var => $val) {
	AppRoot::config("users".$var, $val);
}
?>