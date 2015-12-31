<?php
namespace system
{
	class Module extends \Module
	{
        public $public = false;

		public function __construct()
		{
			$this->moduleName = "api";
			$this->moduleTitle = "api";
		}

		function getContent()
		{
			$section = (\Tools::GET("section"))?\Tools::GET("section"):"cron";
			$action = (\Tools::GET("action"))?\Tools::GET("action"):"cron";
			$arguments = array();

			foreach (explode(",",\Tools::GET("arguments")) as $key => $arg)
			{
				if (strlen(trim($arg)) > 0)
				{
					if ($key == 0)
						$action = $arg;
					else
						$arguments[] = $arg;
				}
			}

			if ($section == "cron")
			{
				$className = "\\".$action."\\Module";
				if (class_exists($className))
				{
					$module = new $className();
					if (method_exists($module, "getCron")) {
						echo $action.": ";
						echo $module->getCron($arguments);
					} else
						echo "<p style='color:red;'>Method ".$className."->getCron() does not exist</p>";
				}
				else
					echo "<p style='color:red;'>".$className." not found</p>";

				if (\AppRoot::doDebug())
					echo \AppRoot::printDebug();

				exit;
			}

			return "Done";
		}

		function getCron($arguments=array())
		{
			$action = "default";
			if (isset($arguments[0]))
				$action = $arguments[0];

			echo $action." ";

			if ($action == "backup")
			{
				$systemController = new \system\controller\System();
				$systemController->doBackup();
				return "done";
			}

			return "unknown action";
		}
	}
}
?>