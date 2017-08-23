<?php
class Modules
{
	public $name = "";

	function __construct($name="")
	{
		$this->name = $name;
	}

	function loadModule()
	{
		AppRoot::debug("Load module: " . $this->name);

		if (!Tools::GET("ajax"))
		{
			$this->loadJavascripts();
			$this->loadStylesheets();
		}

		$class = "\\".$this->name."\\Module";
		return new $class();
	}

    function loadJavascripts()
    {
        \AppRoot::addJavascriptDirectory("modules/" . $this->name . "/javascript", false);
        \AppRoot::addJavascriptDirectory("modules/" . $this->name . "/common/javascript", false);

        if (strlen(trim(\SmartyTools::getTemplate())) > 0) {
            \AppRoot::addJavascriptDirectory("modules/" . $this->name . "/javascript/".\SmartyTools::getTemplate());
            \AppRoot::addJavascriptDirectory("modules/" . $this->name . "/common/javascript/".\SmartyTools::getTemplate());
        }
    }

    function loadStylesheets()
    {
        \AppRoot::addStylesheetDirectory("modules/" . $this->name . "/css", false);
        \AppRoot::addStylesheetDirectory("modules/" . $this->name . "/common/css", false);

        if (strlen(trim(\SmartyTools::getTemplate())) > 0) {
            \AppRoot::addStylesheetDirectory("modules/" . $this->name . "/css/".\SmartyTools::getTemplate());
            \AppRoot::addStylesheetDirectory("modules/" . $this->name . "/common/css/".\SmartyTools::getTemplate());
        }
    }


	/** STATIC **/
	private static $directory = "modules";
	private static $noModuleDir = array(".","..",".svn");
	public static $modules = array();

	public static function getModules($allowedOnly=true)
	{
		if (count(self::$modules) == 0)
		{
			// Load Modules
			\AppRoot::debug("Fetch availible modules");

			self::$modules = array();
			if ($handle = opendir(self::$directory)) {
				while (false !== ($file = readdir($handle))) {
					$filename = self::$directory."/".$file;
					if (is_dir($filename) && !in_array($file, self::$noModuleDir)) {
						self::loadConfig($file);
						self::$modules[$file] = $file;
					}
				}
				closedir($handle);
			}
		}

		asort(self::$modules);
		return self::$modules;
	}

	/**
	 * Get module objects
	 * @return object[]
	 */
	public static function getModuleObjects()
	{
		$modules = array();

		foreach (self::getModules() as $modName)
		{
			$class = "\\".$modName."\Module";
			if (class_exists($class))
				$modules[] = new $class();
		}

		return $modules;
	}

	public static function loadConfig($module)
	{
		$directory = "modules/" . $module . "/config";
		if (file_exists($directory)) {
			if ($handle = opendir($directory)) {
				while (false !== ($file = readdir($handle))) {
					$filename = $directory."/".$file;
					if (is_file($filename)) {
                        AppRoot::debug("Load Config file: ".$filename);
						require_once($filename);
					}
				}
				closedir($handle);
			}
		}
	}

	public static function loadClass($module)
	{
		$directory = "modules/" . $module . "/classes";
		if (file_exists($directory)) {
			if ($handle = opendir($directory)) {
				while (false !== ($file = readdir($handle))) {
					$filename = $directory."/".$file;
					if (is_file($filename)) {
						require_once($filename);
						AppRoot::debug("Load Class file: ".$filename);
					}
				}
				closedir($handle);
			}
		}
	}

	public static function getMainMenu()
	{
		\AppRoot::debug("Build main menu");

		$links = array();
		$submenuExceptions = array("type", "name", "onclick", "url");

		foreach (self::getModules() as $module)
		{
			// Als we geen rechten hebben mogen we deze niet zien.
			if (!\User::getUSER() || !\User::getUSER()->hasRight($module))
				continue;

			// Als er geen sub-items zijn, hoeven we deze niet te zien.
			$submenu = \AppRoot::config($module."submenu");
			if (!$submenu || count($submenu) == 0)
				continue;

            $key = ((\AppRoot::config($module."sortorder"))?(\AppRoot::config($module."sortorder")):90).$module;
			$links[$key]["url"] = (\AppRoot::config($module."url"))?:$module;
			$links[$key]["name"] = ucfirst(\AppRoot::config($module."name"));

			if (isset($submenu[0]["newwindow"]))
				$links[$key]["newwindow"] = $submenu[0]["newwindow"];

			if (is_array($submenu) && count($submenu) > 1)
			{
				foreach ($submenu as $linkkey => $link)
				{
                    if (!isset($link["url"]))
                    {
                        $linkUrlParts = [];
                        if (!isset($link["module"]))
                            $linkUrlParts[] = "module=" . $module;

                        foreach ($link as $var => $val)
                        {
                            if (!in_array($var, $submenuExceptions) && strlen(trim($val)) > 0)
                                $linkUrlParts[] = $var . "=" . $val;
                        }

                        $link["url"] = "index.php?".implode("&", $linkUrlParts);
                    }
					$links[$key]["subMenu"][] = $link;
				}
			}
		}

        ksort($links);

		$mainmenu = \SmartyTools::getSmarty();
		$mainmenu->assign("mainMenu", $links);
		$mainmenu->assign("user", \User::getUSER());
		return $mainmenu->fetch("menu/main");
	}

	public static function getHeader()
	{
		$tpl = \SmartyTools::getSmarty();
		$tpl->assign("mainmenu", \Modules::getMainMenu());
		$tpl->assign("cachetime", filesize("documents/changelog.txt"));
        $tpl->assign("today", \Tools::getDayOfTheWeek().", ".Tools::getWrittenMonth(date("m"))." ".date("d").", ".date("Y"));

		if (\User::getUSER()) {
            $patchNoteController = new \system\controller\PatchNotes();
            if ($patchNoteController->hasNewPatchNotes(\User::getUSER()))
                $tpl->assign("newPatchNotes", 1);
		}

		return $tpl->fetch("header");
	}

	public static function getHomepage()
	{
        // Check characters
        if (\User::getUSER() && count(\User::getUSER()->getAuthorizedCharacters()) == 0)
            \AppRoot::redirect("profile/account");

		\AppRoot::redirect("map");
        return true;
	}
}