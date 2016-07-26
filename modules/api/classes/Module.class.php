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

		function getContent()
		{
			// Set default http code to 500 in case something goes wrong.
			if (function_exists("http_response_code"))
				http_response_code(500);

            $arguments = array();
			$module = \Tools::GET("section");
			$type = \Tools::getRequestType();
			$result = null;
            $api = null;

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


            $className = null;
            if (count($arguments) > 0)
            {
                $params = $arguments; // originele arguments behouden voor als deze class niet gevonden wordt, terug vallen op oude methode.

                $apiName = ucfirst(array_shift($params));
                $className = "\\".$module."\\api\\".$apiName;
                if (!class_exists($className))
                    $className = "\\".$module."\\common\\api\\".$apiName;

                if (class_exists($className))
                {
                    $api = new $className();
                    $methodName = (count($params) > 0) ? array_shift($params) : null;
                    $method = strtolower($type).(($methodName!==null)?ucfirst($methodName):"Default");
                    if (!method_exists($api, $method)) {
                        array_unshift($params, $methodName);
                        $method = strtolower($type)."Default";
                    }

                    if (strtolower($type) == "post") {
                        if (!$postData = file_get_contents("php://input"))
                            return \api\HTTP::getHTTP()->sendNoContent();
                        if (!$postData = json_decode($postData))
                            return \api\HTTP::getHTTP()->sendBadRequest("content json could not be parsed");
                        $result = $api->$method($postData, $params);
                    } else
                        $result = $api->$method($params);
                }
            }

            if ($result === null)
            {
                /* Oude manier */
                $className = "\\" . $module . "\\api\\" . $type;
                if (!class_exists($className))
                    $className = "\\api\\api\\" . $type;

                $api = new $className();
                $result = $api->doRequest($arguments);
            }


            /* Check antwoord */
			if ($result === null)
				$result = \api\HTTP::getHTTP()->sendUnknownRequest();

			// Log request
			$api->addToLog($result, $type, $module);

            \api\HTTP::getHTTP()->sendHTTPCode();

			// Verstuur antwoord
			if (\Tools::REQUEST("debug"))
			{
				// Debugger is aan.. ietsje anders!
				echo "<pre>".print_r($result,true)."</pre>";
			}
			else
			{
                $contentType = \api\HTTP::getHTTP()->getContentType();
				// Normale antwoord
				header('Content-Type: ' . $contentType . '; charset=utf8');
                if ($contentType == 'application/json')
				    echo json_encode($result);
                else
                    echo $result;

                exit;
			}
		}
	}
}
?>