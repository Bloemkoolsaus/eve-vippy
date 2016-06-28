<?php
namespace api
{
	class Client
	{
		public $format = "json";
		public $userAgent;
		public $baseURL;
		public $username;
		public $password;
		public $asArray = false;

        function __construct()
        {
            $this->userAgent = \Config::getCONFIG()->get("system_title");
        }

        private function doRequest($requestType="get", $url, $params=array())
		{
			$result = array();
			$requestURL = $this->baseURL.$url;
			$requestData = json_encode($params);

			if ($requestType == "get")
			{
				$queryString = "";
				foreach ($params as $var => $val) {
					$queryString .= ((strlen(trim($queryString))==0)?"?":"&") . $var . "=" . $val;
				}
				$requestURL = $requestURL.$queryString;
			}


			$curl = curl_init($requestURL);
			curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

			if (strtolower($requestType) == "post")
			{
				curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
															 'Content-Length: ' . strlen($requestData)));
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $requestData);
			}
			else
			{
            	curl_setopt($curl, CURLOPT_HEADER, false);
			}

			// Request uitvoeren!
			$result["requestURL"] = $requestURL;
			$result["requestData"] = $requestData;
			$content = curl_exec($curl);
			$info = curl_getinfo($curl);
			curl_close($curl);

			$result["info"] = $info;
			if ($info["http_code"] != 200)
			    $result["error"] = $info["http_code"];

			// Probeer resultaat te parsen aan de hand van content-type.
			$result["type"] = HTTP::getHTTP()->parseContentType($info["content_type"]);
			switch ($result["type"])
			{
				case "json":
					$result["result"] = json_decode($content, $this->asArray);
					break;
				case "xml":
					$result["result"] = new \SimpleXMLElement($content);
					break;
				default:
					$result["result"] = $content;
					break;
			}

			$this->addToLog($requestURL, $requestData, $info, $content, $requestType, (isset($result["error"])));

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

			$logData = "URL:\n".$requestURL."\n\n";
			$logData .= "REQUEST:\n".$requestData."\n\n";
			$logData .= "RESULT:\n".json_encode($requestInfo)."\n\n";
			$logData .= "RETURN:\n".$requestResult."\n\n";

			//\AppRoot::addToLog($logData, strtolower($requestType), strtolower($logDir));
		}

		function get($url, $params=array())
		{
			$result = $this->doRequest("get", $url, $params);
			return $result;
		}

		function post($url, $params=array())
		{
			$result = $this->doRequest("post", $url, $params);
			return $result;
		}
	}
}
?>