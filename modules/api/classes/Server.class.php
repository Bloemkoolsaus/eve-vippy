<?php
namespace api
{
	class Server
	{
		private $http = null;
		private $user = null;
		private $authenticated = null;

		/**
		 * Get user (if credentials were provided)
		 * @return \users\model\User|null
		 */
		function getUser()
		{
			return $this->user;
		}

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
         * Mag jij wel iets opzoeken in deze api?
         * @return boolean
         */
        function authenticateClient()
        {
            if ($this->authenticated === null)
            {
                $this->authenticated = false;

                // Check api-key headers
                $headers = $this->getHeaders();
                if (isset($headers["API-Key"]) && isset($headers["API-Code"]))
                {
                    foreach (\users\model\User::getUsers() as $user)
                    {
                        if (sha1($user->name) == $headers["API-Key"] && sha1($user->password) == $headers["API-Code"])
                        {
                            $this->user = $user;
                            $this->authenticated = true;
                            break;
                        }
                    }
                }

                // Intern netwerk, altijd ok.
                $ipparts = explode(".",$_SERVER["REMOTE_ADDR"]);
                if ($ipparts[0] == 192 && $ipparts[1] == 168)
                    $this->authenticated = true;
            }

            return $this->authenticated;
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
			$logData .= "USER: ".(($this->getUser()!=null)?$this->getUser()->name:"")."\n";
			$logData .= "CONTENT: ".$this->getContent()."\n";
			$logData .= "SERVER: ".json_encode($_SERVER)."\n";
			$logData .= "HEADER: ".json_encode($this->getHeaders())."\n";

			\AppRoot::addToLog($logData, strtolower($requestType), "api/server/".strtolower($requestModule));
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