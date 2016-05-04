<?php
namespace profile
{
	class Module extends \Module
	{
		public $moduleName = "profile";
		public $moduleTitle = "Profile";


		function getContent()
		{
			$section = (\Tools::REQUEST("section")) ? \Tools::REQUEST("section") : "overview";
			$action = (\Tools::REQUEST("action")) ? \Tools::REQUEST("action") : "overview";

			$controller = new \profile\controller\Profile();

			if ($section == "api")
			{
				$this->moduleTitle = "Profile - API Keys";

				if ($action == "validate")
				{
					$apiController = new \eve\controller\API();
					return $apiController->validate(\Tools::REQUEST("keyid"));
				}
				else
					return $controller->getApiOverview();
			}

			if ($section == "chars")
			{
				$this->moduleTitle = "Profile - Characters";
				return $controller->getCharacterOverview();
			}

			if ($section == "overview")
			{
				$this->moduleTitle = "";
				return $controller->getAccountSettings();
			}

			return parent::getContent();
		}

		function getCron($arguments=array())
		{
			$action = "default";
			if (isset($arguments[0]))
				$action = $arguments[0];

			echo $action." ";

			if ($action == "api")
			{
				$profile = new \profile\console\Profile();
				$profile->checkApiKeys();
				$profile->checkCorporations();
				$profile->checkCharacters();
				return "done";
			}

			return "unknown action";
		}
	}
}
?>