<?php
namespace api\api
{
	class GET extends \api\Server
	{
		function doRequest($arguments)
		{
			$this->authenticateClient();

			$method = "get".ucfirst(trim(strtolower(array_shift($arguments))));
			if (method_exists($this, $method))
				return $this->$method($arguments);

			// Probeer default
			if (method_exists($this, "getDefault"))
				return $this->getDefault($arguments);

			return $this->getHTTP()->sendUnknownRequest();
		}
	}
}
?>