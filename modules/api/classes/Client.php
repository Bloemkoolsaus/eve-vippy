<?php
namespace api;

class Client
{
    public $format = "json";
    public $userAgent = "";
    public $baseURL = "";
    public $username = "";
    public $password = "";
    public $verifySSL = false;

    public $result;
    public $request;
    public $httpStatus = 0;
    public $curlStatus = 0;
    public $sendError = true;
    public $asArray = false;

    private $connectionTimeout = 30;
    private $executionTimeout = 60;
    private $headers = array();
    private $_contentType = "application/json";
    private $_curl = null;

    function __construct($baseURL="")
    {
        $this->baseURL = $baseURL;
        $this->userAgent = \AppRoot::getAppUserAgent();
    }

    function setConnectionTimeout($seconds)
    {
        $this->connectionTimeout = $seconds;
    }

    function setExecutionTimeout($seconds)
    {
        if (\AppRoot::$maxExecTime < $seconds)
            \AppRoot::setMaxExecTime(\AppRoot::$maxExecTime + $seconds);

        $this->executionTimeout = $seconds;
    }

    function addHeader($header)
    {
        $this->headers[] = $header;
    }

    function resetheader()
    {
        $this->headers = [];
    }

    function setContentType($type)
    {
        $this->_contentType = $type;
    }

    function initCurl()
    {
        \AppRoot::debug("Open new CURL connection");
        $this->_curl = curl_init();
        curl_setopt($this->_curl, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, $this->verifySSL);
        curl_setopt($this->_curl, CURLOPT_CONNECTTIMEOUT ,$this->connectionTimeout);
        curl_setopt($this->_curl, CURLOPT_TIMEOUT, $this->executionTimeout);
    }

    /**
     * Get curl handle
     * @return resource|null
     */
    function getCurl()
    {
        if ($this->_curl === null)
            $this->initCurl();

        return $this->_curl;
    }

    function closeCurl()
    {
        \AppRoot::debug("Close CURL connection");
        if ($this->_curl)
            curl_close($this->_curl);

        $this->_curl = null;
    }

    private function doRequest($requestType="get", $url, $params)
    {
        if ($this->executionTimeout == 0)
            \AppRoot::error("API call zonder execution timeout: ".$url);
        if ($this->connectionTimeout == 0)
            \AppRoot::error("API call zonder connection timeout: ".$url);

        $result = array();
        $requestURL = $this->baseURL.$url;
        $requestData = json_encode($params);
        $requestHeaders = $this->headers;

        if ($requestType == "get") {
            $queryString = "";
            foreach ($params as $var => $val) {
                $queryString .= ((strlen(trim($queryString))==0)?"?":"&") . $var . "=" . $val;
            }
            $requestURL = $requestURL.$queryString;
        }

        \AppRoot::debug("*** Start api call: ".strtoupper($requestType)." ".$requestURL);

        curl_setopt($this->getCurl(), CURLOPT_URL, $requestURL);

        if (strtolower($requestType) == "post") {
            \AppRoot::debug($requestData);
            $requestHeaders[] = "Content-Type: ".$this->_contentType;
            $requestHeaders[] = "Content-Length: ".strlen($requestData);
            curl_setopt($this->getCurl(), CURLOPT_POST, true);
            curl_setopt($this->getCurl(), CURLOPT_POSTFIELDS, $requestData);
        } else
            curl_setopt($this->getCurl(), CURLOPT_HEADER, false);

        if (count($requestHeaders) > 0)
            curl_setopt($this->getCurl(), CURLOPT_HTTPHEADER, $requestHeaders);

        // Request uitvoeren!
        $result["requestURL"] = $requestURL;
        $result["requestData"] = $requestData;
        $result["requestHeaders"] = $requestHeaders;
        \AppRoot::debug($result);
        $content = curl_exec($this->getCurl());
        $info = curl_getinfo($this->getCurl());

        $this->request = $info;
        $this->curlStatus = 0;
        $this->httpStatus = $info["http_code"];

        if (curl_errno($this->getCurl())) {
            $this->curlStatus = curl_errno($this->getCurl());
            $result["error"] = "curl(".curl_errno($this->getCurl()).") ".curl_error($this->getCurl());
        }

        if ($this->httpStatus != 200 && $this->httpStatus != 204) {
            $result["error"] = $this->httpStatus;
            \AppRoot::debug($info);
        }

        \AppRoot::debug("RESULT:<br />".$content);

        // Probeer resultaat te parsen aan de hand van content-type.
        $result["info"] = $info;
        \AppRoot::debug("Parse RESULT");
        foreach (explode(";",$info["content_type"]) as $type)
        {
            if (trim($type) == "application/json") {
                \AppRoot::debug("Parse as json");
                $result["result"] = json_decode($content, $this->asArray);
                break;
            } else if (trim($type) == "application/xml") {
                \AppRoot::debug("Parse as xml");
                $result["result"] = new \SimpleXMLElement($content);
                break;
            }
        }

        // Kon niet parsen. Geef ongeparsed.
        if (!isset($result["result"])) {
            \AppRoot::debug("Unknown content-type. Return unparsed.");
            $result["result"] = $content;
        }

        $this->result = $result["result"];
        \AppRoot::debug("Parsed Result:<pre>".print_r($this->result,true)."</pre>");

        // Loggen
        \AppRoot::debug("*** HTTP: ".$this->httpStatus);
        if (isset($result["error"]))
            \AppRoot::error($this->result, null);

        \AppRoot::debug("*** Finish api call: ".strtoupper($requestType)." ".$requestURL);

        return $result;
    }

    function get($url, $params=array())
    {
        $url = str_replace(" ","%20",$url);
        $result = $this->doRequest("get", $url, $params);
        return $result;
    }

    function post($url, $params=array())
    {
        $result = $this->doRequest("post", $url, $params);
        return $result;
    }

    /**
     * Was the request a success?
     * @return boolean
     */
    function success()
    {
        if ($this->curlStatus == 0) {
            if ($this->httpStatus == 200)
                return true;
        }
        return false;
    }

    /**
     * Timeout?
     * @return boolean
     */
    function isTimeout()
    {
        // Check curl
        if ($this->curlStatus == 28)
            return true;

        // http codes
        $timeoutCodes = array(0,403,404,408,410,503,504,522,524);
        if (in_array($this->httpStatus-0, $timeoutCodes))
            return true;
        else
            return false;
    }

    function getRequest()
    {
        return $this->request;
    }

    function getResult()
    {
        return $this->result;
    }
}