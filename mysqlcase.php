<?php
require_once("init.php");
\AppRoot::setMaxExecTime(600);

$db = new \MySQL(array(	"host" => "localhost",
						"user" => "root",
						"pass" => "root",
						"dtbs" => "eve_db_galatea"));
$db->convertToLowerCase();

echo "<p>Done</p>";
echo \AppRoot::printDebug();
?>