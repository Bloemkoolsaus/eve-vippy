<?php
namespace eve\model
{
	class CREST extends \api\Client
	{
		function __construct()
		{
			$this->baseURL = CREST_URL;
		}

		function get($url, $params=array())
		{
			$result = parent::get($url, $params);
			$result["result"] = json_decode($result["result"]);
			return $result;
		}
	}
}
?>