<?php
chdir(__DIR__);
require_once("init.php");

$arguments = array();
if (\AppRoot::isCommandline())
    $arguments = $argv;
else
{
    if (\AppRoot::doDebug())
    {
        \AppRoot::doCliOutput("COMMAND LINE INTERFACE");
        \AppRoot::doCliOutput("Run crons in your browser via: ".APP_URL."system/cron/");
        exit;
    }
}

if (count($arguments) > 1)
{
    $script = array_shift($arguments);
    $moduleName = strtolower(array_shift($arguments));
    $moduleClass = '\\'.$moduleName.'\\Module';
    if (class_exists($moduleClass)) {
        $moduleObject = new $moduleClass();
        if (method_exists($moduleObject, "getCron")) {
            $result = $moduleObject->getCron($arguments);
            \AppRoot::doCliOutput($result);
        } else
            \AppRoot::error("Module ".$moduleName." does not have cron method");
    } else
        \AppRoot::error("Module ".$moduleName." not found");
} else
    \AppRoot::error("Missing arguments");