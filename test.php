<?php
require_once("init.php");

$zaveral = new \eve\model\SolarSystem(30004290);
$jita = new \eve\model\SolarSystem(30000142);
$esesier = new \eve\model\SolarSystem(30003842);
$jan = new \eve\model\SolarSystem(30001385);
$nakah = new \eve\model\SolarSystem(30000072);

$archon = new \eve\model\Ship(23757);

$system = new \eve\controller\SolarSystem();

echo "range: ".$archon->getMaxJumprange(5)."<br />";

if (($jumps = $system->getNrCynoJumps($esesier->id, $nakah->id, 0)) == null)
{
	echo "null";
}
else
{
	echo $jumps;
}

echo \AppRoot::printDebug();
?>