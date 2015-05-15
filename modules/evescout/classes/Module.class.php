<?php
namespace evescout
{
	class Module extends \Module
	{
		private static $eveDB = null;

		public function __construct()
		{
			$this->moduleName = "evescout";
			$this->moduleTitle = "EVE-Scout";
		}

		function getContent()
		{

			return parent::getContent();
		}

		function getCron($arguments=array())
		{
			$action = "default";
			if (isset($arguments[0]))
				$action = $arguments[0];

			echo $action." ";

			if ($action == "import")
			{
				if (!isset($arguments[1]))
					return "Please provide a solarsystem name";

				$import = new \evescout\console\Import();
				$import->importConnections($arguments[1]);
				return $arguments[1]." imported";
			}



			return "unknown action";
		}
	}
}
?>