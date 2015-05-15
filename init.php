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
			$classFile .= ".class.php";
		}
	}

	if (file_exists($classFile))
	{
		require_once($classFile);
		AppRoot::debug("Load Class file: ".$classFile);
	}
	else
		AppRoot::error("Failed to load Class file: ".$classFile);
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

if (!\Tools::REQUEST("ajax"))
{
	// Zoeken naar SQL updates en deze uitvoeren.
	\AppRoot::debug("Check UPDATE SQL");
	$queryRegex = '%\s*((?:\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\' | "[^"\\\\]*(?:\\\\.[^"\\\\]*)*" | /*[^*]*\*+([^*/][^*]*\*+)*/ | \#.* | --.* | [^"\';#])+(?:;|$))%x';
	$directory = "update".DIRECTORY_SEPARATOR."sql";
	if (file_exists($directory))
	{
		$execDirectory = $directory.DIRECTORY_SEPARATOR."exec";
		if ($handle = @opendir($directory))
		{
			while (false !== ($file = readdir($handle)))
			{
				if ($file == "." || $file == "..")
					continue;

				$updateSQLFile = $directory.DIRECTORY_SEPARATOR.$file;
				$execSQLFile = $execDirectory.DIRECTORY_SEPARATOR.$file;
				if (is_file($updateSQLFile) && !file_exists($execSQLFile))
				{
					// Queries parsen.
					preg_match_all($queryRegex, file_get_contents($updateSQLFile), $updateQueries);

					// Queries uitvoeren.
					foreach ($updateQueries[1] as $query) {
						\MySQL::getDB()->doQuery($query);
					}

					// SQL file verplaatsen zodat deze niet nog eens uitgevoerd wordt.
					if (!file_exists($execDirectory))
						mkdir($execDirectory,0777);

					copy($updateSQLFile,$execSQLFile);
				}
			}
			closedir($handle);
		}
	}
	\AppRoot::debug("Finished UPDATE SQL");
}
?>