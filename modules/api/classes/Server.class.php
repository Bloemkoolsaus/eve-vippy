<?php
namespace api
{
	class Server
	{
		function addToLog($resultData, $requestType, $requestModule)
		{
			$logData = "RESULT: ".json_encode($resultData)."\n";
			$logData .= "GET: ".json_encode($_GET)."\n";
			$logData .= "POST: ".json_encode($_POST)."\n";
			$logData .= "CONTENT: ".$this->getContent()."\n";
			$logData .= "SERVER: ".json_encode($_SERVER)."\n";

			\AppRoot::addToLog($logData, strtolower($requestType), "api/server/".strtolower($requestModule));
			if (isset($resultData["error"]))
				\AppRoot::error($logData);
		}

		function getContent()
		{
			if ($content = file_get_contents("php://input"))
				return $content;
			else
				return "";
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

			return false;
		}

		function checkNumericFields($data, $fields)
		{
			foreach ($fields as $field)
			{
				if (!is_numeric($data[$field]))
					return $this->sendDatatypeMismatch($field, "numeric");
			}

			return false;
		}

		function checkArrayFields($data, $fields)
		{
			foreach ($fields as $field)
			{
				if (!is_array($data[$field]))
					return $this->sendDatatypeMismatch($field, "array");
			}

			return false;
		}
	}
}
?>