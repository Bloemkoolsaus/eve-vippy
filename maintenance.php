<?php
chdir(__DIR__);
require_once("init.php");
set_time_limit(600);

$arguments = array();
if (\AppRoot::isCommandline())
    $arguments = $argv;
else {
    if (\Tools::REQUEST("args"))
        $arguments = explode("/",\Tools::REQUEST("args"));
}

$maintenance = new \system\console\Maintenance();
$maintenance->doDefault($arguments);