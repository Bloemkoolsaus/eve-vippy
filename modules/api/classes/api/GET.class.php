<?php
namespace api\api
{
	class GET extends \api\Server
	{
		function doRequest($arguments)
		{
			return \api\HTTP::getHTTP()->sendUnknownRequest();
		}
	}
}
?>