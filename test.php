<?php
require_once("init.php");

$solarsystem = \eve\model\SolarSystem::getSolarsystemByName(\Tools::REQUEST("system"));

$locationTracker = new \map\controller\LocationTracker();
$locationTracker->setCharacterLocation(38, 1899584653, $solarsystem->id, 11188);

echo \AppRoot::printDebug();
?>