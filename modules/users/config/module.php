<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "Users";
$config["public"] = true;
$config["enabled"] = false;

// RIGHTS
$config["rights"]["manageusers"] = ["title" => "Manage Users",
                                    "name" => "manageusers",
                                    "dirdefault" => true];
$config["rights"]["managegroups"] = ["title" => "Manage UserGroups",
                                     "name" => "managegroups",
                                     "dirdefault" => true];

// SET CONFIG
foreach ($config as $var => $val) {
	\AppRoot::config("users".$var, $val);
}