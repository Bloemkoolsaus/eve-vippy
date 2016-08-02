<?php
ini_set("mysql.connect_timeout", 5);
ini_set("display_errors", 1);		// Caught by exception handler
error_reporting(E_ALL);				// Caught by exception handler
header('Content-Type: text/html; charset=ISO-8859-1');
session_start();
$startTime = microtime(true);

// Class loader
function autoLoader($classname)
{
	$classFile = "classes/".$classname.".class.php";
	if (!file_exists($classFile))
	{
		$parts = explode("\\",$classname);
		if (count($parts) > 1)
		{
			$classFile = "modules/".$parts[0]."/classes/";
			for ($i=1; $i<count($parts); $i++) {
				$classFile .= $parts[$i].(($i<count($parts)-1)?"/":"");
			}
            $classFile = (file_exists($classFile.".php")) ? $classFile.".php" : $classFile.".class.php";
		}
	}

	if (file_exists($classFile))
	{
		require_once($classFile);
		\AppRoot::debug("Load Class file: ".$classFile);
	}
	else
		\AppRoot::debug("<span style='color:red;'><b>Failed to load Class file:</b> ".$classFile."</span>", true);
}
spl_autoload_register("autoLoader");

// Error handler
function errorHandler($errno, $errstr, $errfile, $errline)
{
	$aLevels = array(	2 => 'WARNING',
                		8 => 'NOTICE',
                		256 => 'FATAL ERROR',
                		512 => 'WARNING',
                		1024 => 'NOTICE');
	if (isset($aLevels[$errno]))
		$errLevel = $aLevels[$errno];
	else
		$errLevel = "UNKNOWN";

	$message = "ERROR [".$errLevel."] ".$errstr."\n\nFILE: ".$errfile."\nLINE: ".$errline;
	AppRoot::error($message);

	/* Don't execute PHP internal error handler */
	return true;
}
set_error_handler("errorHandler");

// Exception handler
function myExceptionHandler(\Exception $e)
{
	$message = "EXCEPTION [".$e->getCode()."] ".$e->getMessage()."\nFILE: ".$e->getFile()."\nLINE: ".$e->getLine();
	$message .= "\n\n".$e->getTraceAsString();
	AppRoot::error($message);
}
set_exception_handler("myExceptionHandler");


// Load Config
$directory = "config";
if ($handle = @opendir($directory)) {
	while (false !== ($file = readdir($handle))) {
		$filename = $directory."/".$file;
		if (is_file($filename))
			require_once($filename);
	}
	closedir($handle);
}
date_default_timezone_set(TIMEZONE);

// Load Classes
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