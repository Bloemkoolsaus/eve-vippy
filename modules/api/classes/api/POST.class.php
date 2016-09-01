<?php
namespace api\api
{
	class POST extends \api\Server
	{
		function doRequest($arguments)
		{
			if (isset($arguments[0]))
			{
				$method = "action".ucfirst(array_shift($arguments));
				if (method_exists($this, $method))
				{
					// Check of er content is.
					if (!$content = file_get_contents("php://input"))
						return $this->sendNoContent();

					// Check of het geldige JSON is.
					if ($this->postContentJSON())
					{
						$data = json_decode($content, ($this->postContentJSON()=="array")?true:false);
						if ($data == null)
							return $this->sendInvalidContent();
					}
					else
						$data = $content;

					// Request uitvoeren.
					return $this->$method($data, $arguments);
				}
			}

			if (method_exists($this, "actionDefault"))
				return $this->actionDefault($arguments);

			return \api\HTTP::getHTTP()->sendUnknownRequest();
		}

		/**
		 * Is de content van de POST in JSON format?
		 * 		Dit kan wel eens niet zo zijn in het geval van 3e partijen.
		 *
		 * @return boolean
		 */
		function postContentJSON()
		{
			return "object";
		}
	}
}
?>