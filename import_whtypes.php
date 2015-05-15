<?php
require_once("init.php");
\AppRoot::setMaxExecTime(600);


$import = file("documents/import_wh_types.txt");

$systemTypes = array(	"1 w-space"	=> 4,
						"2 w-space"	=> 5,
						"3 w-space"	=> 6,
						"4 w-space"	=> 7,
						"5 w-space"	=> 8,
						"6 w-space"	=> 9,
						"highsec"	=> 1,
						"lowsec"	=> 2,
						"nullsec"	=> 3);

foreach ($import as $i => $line)
{
	if ($i == 0)
		continue;

	$columns = explode("\t", strtolower($line));
	$data = array("name" 	=> strtoupper($columns[0]),
				"destination" => (isset($systemTypes[trim($columns[1])]))?$systemTypes[trim($columns[1])]:0,
				"lifetime"	=> trim(str_replace("hours","",$columns[2])),
				"maxmass"	=> trim(str_replace("kg","",str_replace(",","",$columns[3])))/1000000,
				"jumpmass"	=> trim(str_replace("kg","",str_replace(",","",$columns[5])))/1000000);

	\MySQL::getDB()->updateinsert("mapwormholetypes", $data, array("name" => $data["name"]));
}


echo "<p>Done</p>";
echo \AppRoot::printDebug();
?>