<?php
namespace profile
{
	class Module extends \Module
	{
		public $moduleName = "profile";
		public $moduleTitle = "Profile";

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