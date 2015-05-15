<?php
namespace api
{
	class Module extends \Module
	{
		public function __construct()
		{
			$this->moduleName = "api";
			$this->moduleTitle = "api";
		}

		function getFrontContent()
		{
			$module = \Tools::GET("section");
			$arguments = array();
			$type = \Tools::getRequestType();
			$result = null;

			foreach (explode(",",\Tools::GET("arguments")) as $arg)
			{
				if (strlen(trim($arg)) > 0)
					$arguments[] = $arg;
			}

			$notparams = array("requesturl","module","section","arguments");
			foreach ($_GET as $var => $val)
			{
				if (!in_array($var, $notparams))
				{
					$arguments[] = $var;
					$arguments[] = $val;
				}
			}

			$className = "\\".$module."\\api\\".$type;
			if (!class_exists($className))
				$className = "\\api\\api\\".$type;

			$api = new $className();
			$result = $api->doRequest($arguments);
			if ($result == null)
				$reuslt = \api\HTTP::getHTTP()->sendUnknownRequest();

			// Log request
			$api->addToLog($result, $type, $module);

			// Verstuur antwoord
			if (\Tools::REQUEST("debug"))
			{
				// Debugger is aan.. ietsje anders!
				echo "<pre>".print_r($result,true)."</pre>";
			}
			else
			{
				// Normale antwoord
				header('Content-Type: application/json; charset=utf8');
				echo json_encode($result);
				exit;
			}
		}
	}
}
?>