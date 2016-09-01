<?php
namespace api
{
	class HTTP
	{
		private $httpCode = null;
        private $contentType = 'application/json';

        function setHttpStatus($code)
        {
            $this->httpCode = $code;
        }

		function getHttpStatus()
		{
			if ($this->httpCode !== null)
				return $this->httpCode;

			return 200;
		}

        function sendHTTPCode($code=null)
        {
            if ($code)
                $this->setHttpStatus($code);

            if (function_exists("http_response_code"))
                http_response_code($this->getHttpStatus());

            header("HTTP/1.1 ".$this->getHttpStatus());
            return $this->getHttpStatus();
        }

        function getContentType()
        {
            return $this->contentType;
        }

        function setContentType($contentType)
        {
            $this->contentType = $contentType;
        }

		public static $http = null;

		/**
		 * get http
		 * @return \api\HTTP
		 */
		public static function getHTTP()
		{
			if (self::$http === null)
				self::$http = new \api\HTTP();

			return self::$http;
		}

		function getErrorCode($code=500,$description=false,$details=array(),$mailError=true)
		{
			$error = array();
			$error["code"] = $code;
			if ($description)
				$error["description"] = $description;
			foreach ($details as $key => $value) {
                if (is_array($value) || is_object($value))
                    $value = json_encode($value);
				$error[$key] = $value;
			}

			$errorMessage = "";
			foreach ($error as $var => $val) {
				$errorMessage .= "<b>".$var.":</b> ".$val."<br />";
			}

			self::getHTTP()->setHttpStatus($code);
			return array("error" => $error);
		}

		function sendNoContent($description="No results",$details=array(),$mailError=false)
		{
			return $this->getErrorCode("204",$description,$details,$mailError);
		}

		function sendUnknownRequest($description="Unknown Request",$details=array(),$mailError=false)
		{
			return $this->getErrorCode(404,$description,$details,$mailError);
		}

		function sendBadRequest($description="Invalid Request",$details=array(),$mailError=false)
		{
			return $this->getErrorCode(400,$description,$details,$mailError);
		}

		function sendNotLoggedIn($description="Login Required",$details=array(),$mailError=false)
		{
			return $this->getErrorCode(401,$description,$details,$mailError);
		}

		function sendNotAllowed($description="Insufficient Permissions",$details=array(),$mailError=false)
		{
			return $this->getErrorCode(401,$description,$details,$mailError);
		}

		function sendServerError($description="Herpederp",$details=array(),$mailError=true)
		{
			return $this->getErrorCode(500,$description,$details,$mailError);
		}

		function sendMissingField($field,$mailError=false)
		{
			return $this->sendBadRequest($field . " missing", array(), $mailError);
		}

		function sendEmptyField($field,$mailError=false)
		{
			return $this->sendBadRequest($field . " empty", array(), $mailError);
		}

		function sendDatatypeMismatch($field, $type, $mailError=false)
		{
			return $this->sendBadRequest("Datatype mismatch for ".$type." field: ".$field, array(), $mailError);
		}
	}
}
?>