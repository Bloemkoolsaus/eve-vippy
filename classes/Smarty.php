<?php
require_once("classes/smarty/Smarty.class.php");
class SmartyTools
{
	private static $templatedir = "templates";
	private static $template = "default";

	public static function getTemplateDir($module=false, $template=null)
	{
		if (!$module)
			$dir = self::$templatedir."/";
		else
			$dir = "modules/".$module."/".self::$templatedir."/";

		if ($template == null)
			$template = self::getTemplate();

		if (strlen(trim($template)) > 0)
			$dir .= $template."/";

		if (file_exists($dir))
		{
			\AppRoot::debug("GetTemplateDir: " . $dir);
			return $dir;
		}
		else
		{
			if ($template != "default")
				return self::getTemplateDir($module, "default");
			else
			{
				\AppRoot::error("GetTemplateDir: Directory does not exist: " . $dir);
				return false;
			}
		}
	}

	public static function setTemplate($tpl="default") {
		self::$template = $tpl;
	}

	public static function getTemplate() {
		return self::$template;
	}

	/**
	 * Default Template Handler - Called when Smarty's file: resource is unable to load a requested file
	 * @param string    $type       resource type (e.g. "file", "string", "eval", "resource")
	 * @param string    $name       resource name (e.g. "foo/bar.tpl")
	 * @return string|boolean       path to file or boolean false if no default template could be loaded
	 */
	public static function getSmartyTemplate($type, $name, $template=null)
	{
		if ($type != "file")
			return false;

		if ($template == null)
			$template = \SmartyTools::getTemplate();

		// Pak de template in de goede map
		$file = "templates/".$template."/".$name.".html";
		$file = str_replace(".html.html",".html",$file);

		// Misschien is het uit een module.
		if (!file_exists($file))
		{
			$i = 0;
			foreach (explode("/", $name) as $part)
			{
				if ($i == 0)
					$file = "modules/".$part."/templates/".$template;
				else
					$file .= "/".$part;

				$i++;
			}
			$file .= ".html";
		}
		$file = str_replace(".html.html",".html",$file);

		\AppRoot::debug("TEMPLATE FILE: ".$file);
		if (!file_exists($file))
		{
			if ($template != "default")
				return self::getSmartyTemplate($type, $name, "default");
			else
			{
				\AppRoot::error("Template file not found: ".$file);
				return false;
			}
		}

		return $file;
	}

	/**
	 * Get smarty template
	 * @return \Smarty
	 */
	public static function getSmarty()
	{
		$smarty = new \Smarty();
		$smarty->setPluginsDir("classes/smarty/plugins");
		$smarty->setCompileDir(self::getCompiledDir());
		$smarty->default_template_handler_func = "SmartyTools::getSmartyTemplate";
		$smarty->assign("App", self::getAppData());
		return $smarty;
	}

	/**
	 * Get application data
	 * @return \stdClass
	 */
	public static function getAppData()
	{
		$appData = new \stdClass();
		$appData->url = \Config::getCONFIG()->get("system_url");
		$appData->browser = \Tools::getBrowser();
		$appData->datetime = date("Y-m-d H:i:s");
		$appData->theme = self::getTemplate();

		foreach (\Modules::getModuleObjects() as $module) {
			if (method_exists($module, "getAppData"))
				$appData = $module->getAppData($appData);
		}

		return $appData;
	}

	private static function getCompiledDir()
	{
        $directory = \Config::getCONFIG()->get("system_document_dir")."smarty/compiled";

		// Check if directory exists.
		if (!file_exists($directory))
		{
			$compiledDirectory = "";
			foreach (explode("/", $directory) as $part) {
				if (strlen(trim($part)) > 0) {
					$compiledDirectory .= ((strlen(trim($compiledDirectory))>0)?"/":"").$part;
					if (!file_exists($compiledDirectory))
						mkdir($compiledDirectory,0777);
				}
			}
		}

		return $directory;
	}
}