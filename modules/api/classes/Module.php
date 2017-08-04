<?php
namespace api;

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

        foreach (explode(",",\Tools::GET("arguments")) as $arg) {
            if (strlen(trim($arg)) > 0)
                $arguments[] = $arg;
        }

        $notparams = array("requesturl","module","section","arguments","apikey","debug");
        foreach ($_GET as $var => $val) {
            if (!in_array($var, $notparams)) {
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
            \AppRoot::debug("API REQUEST: ".$className);
            if (!class_exists($className)) {
                $className = "\\".$module."\\common\\api\\".$apiName;
                \AppRoot::debug("API controller not found. Look for: ".$className);
            }

            if (class_exists($className))
            {
                /** @var \api\Server $api */
                $api = new $className();
                $methodName = (count($params) > 0) ? array_shift($params) : null;
                $method = strtolower($type).(($methodName!==null)?ucfirst($methodName):"Default");
                if (!method_exists($api, $method)) {
                    array_unshift($params, $methodName);
                    $method = strtolower($type)."Default";
                }
                \AppRoot::debug("API REQUEST: ".$method."()");

                if ($api->authenticateClient()) {
                    if (strtolower($type) == "post") {
                        if (!$postData = file_get_contents("php://input"))
                            $result = \api\HTTP::getHTTP()->sendNoContent();
                        else if (!$postData = json_decode($postData))
                            $result = \api\HTTP::getHTTP()->sendBadRequest("content json could not be parsed");
                        else
                            $result = $api->$method($postData, $params);
                    } else
                        $result = $api->$method($params);
                } else
                    $result = \api\HTTP::getHTTP()->sendNotAllowed();
            }
        }

        /* Check antwoord */
        if ($result === null)
            $result = \api\HTTP::getHTTP()->sendUnknownRequest();

        \api\HTTP::getHTTP()->sendHTTPCode();

        // Verstuur antwoord
        if (\Tools::REQUEST("debug") && (defined("APP_DEBUG") && APP_DEBUG == true))
        {
            // Debugger is aan.. ietsje anders!
            echo "<pre>".print_r($result,true)."</pre>";
        }
        else
        {
            // Normale antwoord
            $contentType = \api\HTTP::getHTTP()->getContentType();
            header('Content-Type: ' . $contentType . '; charset=utf8');
            if ($contentType == 'application/json')
                echo json_encode($result);
            else
                echo $result;

            exit;
        }

        return null;
    }
}