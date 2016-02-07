<?php
// DEFAULT CONFIGURATION
$config = array();
$config["name"] = "Statistics";
$config["public"] = true;
$config["enabled"] = (\User::getUSER()->isAuthorized()) ? true : false;

$config["submenu"][] = array("type" => "link",
							"name"	=> "Leaderboard",
							"section"=> "leaderboard");

if (\User::getUSER()->isAdmin())
{
	$config["submenu"][] = array("type" => "link",
								"name"	=> "Statistics",
								"section"=> "full");

	$doRaffles = false;
	foreach (\User::getUSER()->getAuthGroups() as $group)
	{
		if ($group->hasModule("stats"))
			$doRaffles = true;
	}
	if ($doRaffles)
	{
		$config["submenu"][] = array("type" => "link",
									"name"	=> "Monthly Raffle",
									"section"=> "raffle");

	}
}

// SET CONFIG
foreach ($config as $var => $val) {
	\AppRoot::config("stats".$var, $val);
}
?>