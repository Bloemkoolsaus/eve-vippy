<?php
namespace api
{
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

		function __construct($baseURL="")
		{
			$this->baseURL = $baseURL;
            $this->userAgent = \Config::getCONFIG()->get("system_title");
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

			if ($requestType == "get")
			{
				$queryString = "";
				foreach ($params as $var => $val) {
					$queryString .= ((strlen(trim($queryString))==0)?"?":"&") . $var . "=" . $val;
				}
				$requestURL = $requestURL.$queryString;
			}

			\AppRoot::debug("*** Start api call: ".strtoupper($requestType)." ".$requestURL);

			$curl = curl_init($requestURL);
			curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->verifySSL);

			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT ,$this->connectionTimeout);
			curl_setopt($curl, CURLOPT_TIMEOUT, $this->executionTimeout);

			if (strtolower($requestType) == "post")
			{
				\AppRoot::debug($requestData);
				$requestHeaders[] = "Content-Type: application/json";
				$requestHeaders[] = "Content-Length: ".strlen($requestData);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $requestData);
			}
			else
				curl_setopt($curl, CURLOPT_HEADER, false);


			if (count($requestHeaders) > 0)
				curl_setopt($curl, CURLOPT_HTTPHEADER, $requestHeaders);


			// Request uitvoeren!
			$result["requestURL"] = $requestURL;
			$result["requestData"] = $requestData;
			$result["requestHeaders"] = $requestHeaders;
			$content = curl_exec($curl);
			$info = curl_getinfo($curl);

			$this->request = $info;
			$this->curlStatus = 0;
			$this->httpStatus = $info["http_code"];

			if (curl_errno($curl))
			{
				$this->curlStatus = curl_errno($curl);
				$result["error"] = "curl(".curl_errno($curl).") ".curl_error($curl);
			}

			curl_close($curl);

			if ($this->httpStatus != 200 && $this->httpStatus != 204)
			    $result["error"] = $this->httpStatus;


			// Probeer resultaat te parsen aan de hand van content-type.
			$result["info"] = $info;
			foreach (explode(";",$info["content_type"]) as $type)
			{
			    if (trim($type) == "application/json") {
			        $result["result"] = json_decode($content, $this->asArray);
			        break;
			    } else if (trim($type) == "application/xml") {
			        $result["result"] = new \SimpleXMLElement($content);
			        break;
			    }
			}

			// Kon niet parsen. Geef ongeparsed.
			if (!isset($result["result"]))
			    $result["result"] = $content;

			$this->result = $result["result"];


			// Loggen
			\AppRoot::debug("Result: ".$this->httpStatus);
			if (isset($result["error"]))
				\AppRoot::error($this->result);

			\AppRoot::debug("*** Finish api call: ".strtoupper($requestType)." ".$requestURL);

			$sendErrorLog = false;
			if ($this->sendError && isset($result["error"]))
				$sendErrorLog = true;

			$this->addToLog($requestURL, $requestData, $info, $content, $requestType, $sendErrorLog);

			return $result;
		}

		private function addToLog($requestURL, $requestData, $requestInfo, $requestResult, $requestType, $sendMail=false)
		{
			$logURL = $requestURL;
			$logURL = str_replace("http://","",$logURL);
			$logURL = str_replace("https://","",$logURL);
			$logURL = explode("/",$logURL);
			$logURL = \Tools::formatFilename($logURL[0]);

			$logDir = "api/client";
			$logDir .= "/".$logURL;

			$logData = "URL:\t".$requestURL."\n";
			$logData .= "HTTP:\t".$this->httpStatus."\n\n";
			$logData .= "REQUEST:\n\t".$requestData."\n\n";
			$logData .= "RETURN:\n\t".$requestResult."\n\n";
			$logData .= "RESULT:\n\t".json_encode($requestInfo)."\n\n";
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
			if ($this->curlStatus == 0)
			{
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
			return json_decode($this->result, $this->asArray);
		}
	}
}
?>