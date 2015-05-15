<?php
namespace whinfo
{
	class Module extends \Module
	{
		public $moduleName = "whinfo";
		public $moduleTitle = "WH Information";

		function getContent()
		{
			$view = (\Tools::REQUEST("section")) ?: "colors";
			$class = "\\whinfo\\view\\".ucfirst($view);
			\AppRoot::debug("whinfo: ".$class);
			if (class_exists($class))
			{
				$view = new $class();
				return $view->getOverview();
			}


			return parent::getContent();
		}
	}
}
?>