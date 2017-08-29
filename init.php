<?php
ini_set("display_errors", 1);		// Caught by exception handler
error_reporting(E_ALL);				// Caught by exception handler

header('Content-Type: text/html; charset=utf-8');
ini_set("mysql.connect_timeout", 5);
ini_set("default_charset", "utf-8");
date_default_timezone_set("UTC");   // EVE server-time

session_start();
$startTime = microtime(true);

// Class loader
require_once("classes/AppRoot.php");
function autoLoader($classname) {
    \AppRoot::classLoader($classname);
}
spl_autoload_register("autoLoader");

// Error handler
function errorHandler($errno, $errstr, $errfile, $errline) {
    \AppRoot::errorHandler($errno, $errstr, $errfile, $errline);
}
set_error_handler("errorHandler");

// Exception handler
function myExceptionHandler($e) {
    \AppRoot::exceptionHandler($e);
}
set_exception_handler("myExceptionHandler");


// Load Config & Classes
$directories = array("config");
foreach ($directories as $directory) {
    if ($handle = @opendir($directory)) {
        while (false !== ($file = readdir($handle))) {
            // Check extensie
            if (pathinfo($file, PATHINFO_EXTENSION) != "php")
                continue;
            $filename = $directory."/".$file;
            if (is_file($filename))
                require_once($filename);
        }
    }
}

// Load Classes
require_once("classes/AppRoot.php");
$directory = "classes";
if ($handle = @opendir($directory)) {
	while (false !== ($file = readdir($handle))) {
		$filename = $directory."/".$file;
		if (is_file($filename))
		    require_once($filename);
	}
	closedir($handle);
}

\AppRoot::$startTime = $startTime;
\AppRoot::debug("Initializing");
\AppRoot::parseRequestURL();