<?php
namespace api\api
{
	class POST extends \api\Server
	{
		function doRequest($arguments)
		{
			return \api\HTTP::getHTTP()->sendUnknownRequest();
		}
	}
}
?>