<?php
namespace api
{
	class Server
	{
		private $http = null;
        private $authGroup = null;

        /**
		 * Get apache request headers (when available)
		 * @return array
		 */
		function getHeaders()
		{
			if (function_exists('apache_request_headers')) {
				return apache_request_headers();
			}

			return array();
		}

        /**
         * Get POST contents
         * @return string|null
         */
        function getPostContent()
        {
            $content = file_get_contents("php://input");
            if ($content)
                return json_decode($content);

            return null;
        }

        /**
         * Get authgroup
         * @return \admin\model\AuthGroup|null
         */
        function getAuthGroup()
        {
            return $this->authGroup;
        }

        /**
         * Mag jij wel iets opzoeken in deze api?
         * @return boolean
         */
        function authenticateClient()
        {
            if ($this->authGroup === null)
            {
                /** @var \api\model\Key $apikey */
                $apikey = null;
                if (\Tools::REQUEST("apikey"))
                    $apikey = \api\model\Key::findOne(["apikey" => \Tools::REQUEST("apikey")]);

                if ($apikey)
                {
                    // Check ips
                    $correctIP = false;
                    foreach ($apikey->getIPAddresses() as $address) {
                        if ($address->ipAddress == \AppRoot::getClientIP())
                            $correctIP = true;
                    }

                    if ($correctIP)
                    {
                        $this->authGroup = $apikey->getAuthgroup();
                        if ($this->authGroup)
                            return true;
                    }
                }
            }

            return false;
        }

        /**
         * Add to log
         * @param $resultData
         * @param $requestType
         * @param $requestModule
         */
		function addToLog($resultData, $requestType, $requestModule)
		{
			$logData = "RESULT: ".json_encode($resultData)."\n";
			$logData .= "GET: ".json_encode($_GET)."\n";
			$logData .= "POST: ".json_encode($_POST)."\n";
			$logData .= "CONTENT: ".$this->getContent()."\n";
			$logData .= "SERVER: ".json_encode($_SERVER)."\n";
			$logData .= "HEADER: ".json_encode($this->getHeaders())."\n";

			if (isset($resultData["error"]) && isset($resultData["error"]["code"]))
            {
                // Error string. Was it really an error?
                $httpCode = $resultData["error"]["code"]-0;
                if ($httpCode >= 400 && $httpCode <= 600)
                    \AppRoot::error($logData);
            }
		}

		/**
		 * Get http
		 * @return \api\HTTP
		 */
		function getHTTP()
		{
			if ($this->http == null)
				$this->http = \api\HTTP::getHTTP();

			return $this->http;
		}

		function getContent()
		{
			if ($content = file_get_contents("php://input"))
				return $content;
			else
				return "";
		}

        function sendBadRequest($message="Bad request")
        {
            return \api\HTTP::getHTTP()->sendBadRequest($message);
        }

		function sendNoContent()
		{
			return \api\HTTP::getHTTP()->sendBadRequest("No content");
		}

		function sendInvalidContent($format="JSON")
		{
			return \api\HTTP::getHTTP()->sendBadRequest("Content is not valid ".$format);
		}

		function sendMissingField($field)
		{
			return \api\HTTP::getHTTP()->sendMissingField($field);
		}

		function sendEmptyField($field)
		{
			return \api\HTTP::getHTTP()->sendEmptyField($field);
		}

		function sendDatatypeMismatch($field, $type)
		{
			return \api\HTTP::getHTTP()->sendDatatypeMismatch($field, $type);
		}


		function checkRequiredFields($data, $fields)
		{
			foreach ($fields as $field)
			{
				if (is_array($data))
				{
					if (!isset($data[$field]))
						return $this->sendMissingField($field);
					else
					{
						if (is_array($data[$field]))
						{
							if (count($data[$field]) == 0)
								return $this->sendEmptyField($field);
						}
						else
						{
							if (strlen(trim($data[$field])) == 0)
								return $this->sendEmptyField($field);
						}
					}
				}
				else
				{
					if (!isset($data->$field))
						return $this->sendMissingField($field);
					else
					{
						if (is_array($data->$field))
						{
							if (count($data->$field) == 0)
								return $this->sendEmptyField($field);
						}
						else
						{
							if (is_object($data->$field))
							{
								if (count(get_object_vars($data->$field)) == 0)
									return $this->sendEmptyField($field);
							}
							else
							{
								if (strlen(trim($data->$field)) == 0)
									return $this->sendEmptyField($field);
							}
						}
					}
				}
			}

			return false;
		}

		function checkStringFields($data, $fields)
		{
			foreach ($fields as $field)
			{
				if (isset($data[$field]))
				{
					if (is_object($data[$field]) || is_array($data[$field]) || is_bool($data[$field]))
						return $this->sendDatatypeMismatch($field, "string", gettype($data[$field]));
				}
			}

			return false;
		}

		function checkNumericFields($data, $fields)
		{
			foreach ($fields as $field)
			{
				if (is_array($data))
				{
					if (!is_numeric($data[$field]))
						return $this->sendDatatypeMismatch($field, "numeric");
				}
				else
				{
					if (!is_numeric($data->$field))
						return $this->sendDatatypeMismatch($field, "numeric");
				}
			}

			return false;
		}

		function checkArrayFields($data, $fields)
		{
			foreach ($fields as $field)
			{
				if (is_array($data))
				{
					if (!is_array($data[$field]))
						return $this->sendDatatypeMismatch($field, "array");
				}
				else
				{
					if (!is_array($data->$field))
						return $this->sendDatatypeMismatch($field, "array");
				}
			}

			return false;
		}
	}
}
?>