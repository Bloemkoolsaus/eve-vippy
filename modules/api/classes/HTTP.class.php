<?php
namespace api
{
	class HTTP
	{
		public static function getHTTP()
		{
			return new HTTP();
		}

		function parseContentType($contenttype)
		{
			foreach (explode(";",$contenttype) as $type) {
				foreach (explode("/",$type) as $part) {
					if (in_array(trim($part), array("json","xml")))
						return trim($part);
				}
			}

			return "unknown";
		}

		function getErrorCode($code=500,$description=false,$details=array())
		{
			$error = array();
			$error["code"] = $code;
			if ($description)
				$error["description"] = $description;
			foreach ($details as $key => $value) {
				$error[$key] = $value;
			}

			header("HTTP/1.0 ".$code);
			return array("error" => $error);
		}

		function sendUnknownRequest($description="Unknown Request",$details=array())
		{
			return $this->getErrorCode(404,$description,$details);
		}

		function sendBadRequest($description="Invalid Request",$details=array())
		{
			return $this->getErrorCode(400,$description,$details);
		}

		function sendNotLoggedIn($description="Login Required",$details=array())
		{
			return $this->getErrorCode(401,$description,$details);
		}

		function sendNotAllowed($description="Insufficient Permissions",$details=array())
		{
			return $this->getErrorCode(403,$description,$details);
		}

		function sendServerError($description="Whoops",$details=array())
		{
			return $this->getErrorCode(500,$description,$details);
		}

		function sendMissingField($field)
		{
			return $this->sendBadRequest($field . " missing");
		}

		function sendEmptyField($field)
		{
			return $this->sendBadRequest($field . " empty");
		}

		function sendDatatypeMismatch($field, $type)
		{
			return $this->sendBadRequest("Datatype mismatch for ".$type." field: ".$field);
		}
	}
}
?>