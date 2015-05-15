<?php
require_once("init.php");
\AppRoot::setMaxExecTime(600);


$import = file("documents/import_c4_statis.txt");

$region = "";

$systemClasses = array(	1 => 4,
						2 => 5,
						3 => 6,
						4 => 7,
						5 => 8,
						6 => 9);

foreach ($import as $line)
{
	$line = strtolower($line);
	$line = str_replace("(community input)","",$line);

	if (substr($line, 0, 6) == "region")
	{
		$region = "110000".trim(str_replace("region", "", $line));
	}
	else
	{
		$parts = explode("-", $line);
		$constellation = trim($parts[0]);
		if (strlen(trim($constellation)) == 0)
			continue;

		$statics = explode("/", str_replace("c","",$parts[1]));
		$newstatic = trim($statics[1]);
		if ($newstatic !== "xx")
		{
			$newstatic = $systemClasses[$newstatic];

			// Haal wormhole-type-id
			if ($result = \MySQL::getDB()->getRow("	SELECT  t.id
													FROM    mapwormholetypespawns s
													    INNER JOIN mapwormholetypes t on t.id = s.whtypeid
													WHERE   s.fromclass = 7
													AND	    t.whtype = 'static'
													AND	    t.destination = ?"
										, array($newstatic)))
			{
				$whTypeID = $result["id"];

				// Zoek systems in deze constellation
				if ($results = \MySQL::getDB()->getRows("SELECT	*
														FROM	".\eve\Module::eveDB().".mapsolarsystems
														WHERE	regionid = ?
														AND		constellationid LIKE '%".$constellation."'"
											, array($region)))
				{
					foreach ($results as $result)
					{
						\MySQL::getDB()->insert("mapwormholestatics", array("solarsystemid"	=> $result["solarsystemid"],
																			"whtypeid" => $whTypeID));
					}
				}
			}
		}
	}
}


echo "<p>Done</p>";
echo \AppRoot::printDebug();
?>