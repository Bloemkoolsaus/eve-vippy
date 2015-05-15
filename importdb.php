<?php
require_once("init.php");
\AppRoot::setMaxExecTime(0);
$db = \MySQL::getDB();

$db->doQuery("TRUNCATE mapwormholetypes");
$db->doQuery("TRUNCATE mapwormholetypespawns");

$lines = file("whtypes.txt");
$currentClassID = 0;
for ($i=0; $i<count($lines); $i++)
{
	$line = $lines[$i];
	if (strlen(trim($line)) == 0 || substr(trim($line),0,4) == "note")
		continue;

	if (trim(strtolower(substr($line,0,12))) == "wormholes to")
	{
		switch (strtolower(trim(str_replace("wormholes to","",strtolower($line)))))
		{
			case "class 1":
				$currentClassID = 4;
				break;
			case "class 2":
				$currentClassID = 5;
				break;
			case "class 3":
				$currentClassID = 6;
				break;
			case "class 4":
				$currentClassID = 7;
				break;
			case "class 5":
				$currentClassID = 8;
				break;
			case "class 6":
				$currentClassID = 9;
				break;
			case "high sec":
				$currentClassID = 1;
				break;
			case "low sec":
				$currentClassID = 2;
				break;
			case "null sec":
				$currentClassID = 3;
				break;
			default:
				$currentClassID = 0;
				break;
		}

		$i += 2;
		continue;
	}

	$parts = explode("\t",$line);

	$data = array(	"name"		=> strtoupper(trim($parts[0])),
					"whtype"	=> strtolower(trim($parts[1])),
					"destination" => $currentClassID,
					"lifetime"	=> trim(str_replace("hrs","",strtolower($parts[6]))),
					"jumpmass"	=> trim(str_replace("gg","",strtolower($parts[7]))),
					"maxmass"	=> trim(str_replace("gg","",strtolower($parts[8]))));
	$whTypeID = $db->insert("mapwormholetypes", $data);

	$departures = array();
	switch (strtolower(trim($parts[2])))
	{
		case "class 1":
			$departures[] = 4;
			break;
		case "class 2":
			$departures[] = 5;
			break;
		case "class 3":
			$departures[] = 6;
			break;
		case "class 4":
			$departures[] = 7;
			break;
		case "class 5":
			$departures[] = 8;
			break;
		case "class 6":
			$departures[] = 9;
			break;
		case "class 5 & 6":
			$departures[] = 8;
			$departures[] = 9;
			break;
		case "?":
			for ($j=1; $j<=9; $j++) {
				$departures[] = $j;
			}
			break;
		default:
			if (strpos(strtolower(trim($parts[2])),"high") !== false)
				$departures[] = 1;
			if (strpos(strtolower(trim($parts[2])),"low") !== false)
				$departures[] = 2;
			if (strpos(strtolower(trim($parts[2])),"null") !== false)
				$departures[] = 3;
			break;
	}

	foreach ($departures as $dep)
	{
		$data = array(	"whtypeid"	=> $whTypeID,
						"fromclass"	=> $dep,
						"toclass"	=> $currentClassID);
		$whSpawnID = $db->insert("mapwormholetypespawns", $data);
	}
}


$db->doQuery("TRUNCATE mapwormholestatics");
$lines = file("whstatics.txt");
for ($i=0; $i<count($lines); $i++)
{
	$line = $lines[$i];
	if (strpos($line,"Constellation") === 0 && strpos($line,"System Static") !== false)
	{
		$parts = explode(":",$line);
		$constellationID = trim(str_replace("constellation","",strtolower($parts[0]))) + 21000000;

		$solarSystemIDs = array();
		if ($results = $db->getRows("SELECT * FROM mapsolarsystems WHERE constellationid = ?", array($constellationID)))
		{
			foreach ($results as $result) {
				$solarSystemIDs[] = $result["solarSystemID"];
			}
		}

		$whtypes = explode("(",$parts[1]);
		$whtypes = strtolower($whtypes[0]);
		$whtypes = str_replace("system static is","",$whtypes);
		$whtypes = str_replace("statics are","",$whtypes);
		$whtypes = str_replace("system","",$whtypes);
		$whtypes = str_replace("to high sec","",$whtypes);
		$whtypes = str_replace("to low sec","",$whtypes);
		$whtypes = str_replace("to null sec","",$whtypes);
		$whtypes = str_replace("to class 1","",$whtypes);
		$whtypes = str_replace("to class 2","",$whtypes);
		$whtypes = str_replace("to class 3","",$whtypes);
		$whtypes = str_replace("to class 4","",$whtypes);
		$whtypes = str_replace("to class 5","",$whtypes);
		$whtypes = str_replace("to class 6","",$whtypes);
		$whtypes = explode("and",$whtypes);

		$whTypeIDs = array();
		foreach ($whtypes as $whtype)
		{
			if ($result = $db->getRow("SELECT * FROM mapwormholetypes WHERE name = ?", array(strtoupper($whtype))))
				$whTypeIDs[] = $result["id"];
		}

		foreach ($solarSystemIDs as $systemid) {
			foreach ($whTypeIDs as $typeid) {
				$db->insert("mapwormholestatics", array("solarsystemid" => $systemid, "whtypeid" => $typeid));
			}
		}
	}
}


// Render templates
$mainTPL = \SmartyTools::getSmarty();
$mainTPL->assign("javascript", $javascripts);
$mainTPL->assign("stylesheet", $stylesheets);
AppRoot::title(APP_TITLE);

// Finish page
$mainTPL->assign("pageTitle", AppRoot::getTitle());
$mainTPL->assign("mainmenu", "");
$mainTPL->assign("maincontent", "");

if (User::getUSER() && User::getUSER()->loggedIn())
	$mainTPL->assign("userFullname", User::getUSER()->getFullName());

if (Tools::getCurrentSystem())
	$mainTPL->assign("location", Tools::getCurrentSystem());
else
	$mainTPL->assign("today", Tools::getDayOfTheWeek().", ".Tools::getWrittenMonth(date("m"))." ".date("d").", ".date("Y"));

$browser = Tools::getBrowser();
$mainTPL->assign("browser", $browser["name"]. " ".$browser["version"]);
$mainTPL->assign("cachetime",time());

AppRoot::debug("Finishing");
$mainTPL->assign("debug", AppRoot::printDebug());

if (Tools::REQUEST("ajax") && Tools::REQUEST("debug") != "1")
	$mainTPL->display(SmartyTools::getTemplateDir()."ajax.html");
else
	$mainTPL->display(SmartyTools::getTemplateDir()."index.html");
?>