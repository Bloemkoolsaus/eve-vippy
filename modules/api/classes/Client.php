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

    protected $request;
    protected $result;

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

        $this->request = new \stdClass();
        $this->request->url = $this->baseURL.$url;
        $this->request->type = strtoupper($requestType);

        if ($requestType == "get") {
            $queryString = "";
            foreach ($params as $var => $val) {
                $queryString .= ((strlen(trim($queryString))==0)?"?":"&") . $var . "=" . $val;
            }
            $this->request->url .= $queryString;
        }

        curl_setopt($this->getCurl(), CURLOPT_URL, $this->request->url);
        curl_setopt($this->getCurl(), CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($this->getCurl(), CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->getCurl(), CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->getCurl(), CURLOPT_SSL_VERIFYPEER, $this->verifySSL);
        curl_setopt($this->getCurl(), CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
        curl_setopt($this->getCurl(), CURLOPT_TIMEOUT, $this->executionTimeout);
        curl_setopt($this->getCurl(), CURLOPT_HEADER, true);

        if (strtolower($requestType) == "post") {
            $this->request->content = json_encode($params);
            //$this->request->content = http_build_query($params);
            $this->addHeader("Content-Type: ".$this->_contentType);
            $this->addHeader("Content-Length: ".strlen($this->request->content));
            curl_setopt($this->getCurl(), CURLOPT_POST, true);
            curl_setopt($this->getCurl(), CURLOPT_POSTFIELDS, $this->request->content);
        }

        if (count($this->headers) > 0) {
            $this->request->headers = $this->headers;
            curl_setopt($this->getCurl(), CURLOPT_HTTPHEADER, $this->request->headers);
        }

        // Request uitvoeren!
        \AppRoot::debug("*** Start api call: ".$this->request->type." ".$this->request->url);
        \AppRoot::debug($this->request);

        $this->result = new \stdClass();

        $response = curl_exec($this->getCurl());
        $this->result->info = curl_getinfo($this->getCurl());
        $this->result->content = substr($response, $this->result->info["header_size"]);

        // Parse result body
        foreach (explode(";", $this->result->info["content_type"]) as $type) {
            if (trim($type) == "application/json") {
                \AppRoot::debug("Parse as json");
                $this->result->content = json_decode($this->result->content, $this->asArray);
                break;
            } else if (trim($type) == "application/xml") {
                \AppRoot::debug("Parse as xml");
                $this->result->content = new \SimpleXMLElement($this->result->content);
                break;
            }
        }

        // Parse result headers
        $this->result->headers = [];
        foreach (explode("\n", substr($response, 0, $this->result->info["header_size"])) as $data) {
            $data = explode(":", $data);
            $header = str_replace("\n", "", array_shift($data));
            $value = str_replace("\n", "", implode(":", $data));
            if (strlen(trim($header)) > 0)
                $this->result->headers[$header] = trim($value);
        }

        $this->curlStatus = null;
        $this->httpStatus = $this->result->info["http_code"];
        if (curl_errno($this->getCurl())) {
            $this->curlStatus = curl_errno($this->getCurl());
            $this->result->error = "curl(".curl_errno($this->getCurl()).") ".curl_error($this->getCurl());
        }


        \AppRoot::debug("*** Finish api call: ".$this->request->type." ".$this->request->url);
        \AppRoot::debug($this->result);

        return $this->result;
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

        return false;
    }

    function getRequest()
    {
        return $this->request;
    }

    function getResult()
    {
        if (isset($this->result->content))
            return $this->result->content;

        return null;
    }

    function getResultHeaders()
    {
        if (isset($this->result->headers))
            return $this->result->headers;

        return null;
    }

    function getResultHeader($header)
    {
        if (isset($this->result->headers)) {
            if (isset($this->result->headers[$header]))
                return $this->result->headers[$header];
        }

        return null;
    }
}