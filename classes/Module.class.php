<?php
class Module
{
	public $moduleName = "module";
	public $moduleTitle = "Module";
	public $moduleContent = "";
	public $moduleSection = null;
    public $public = true;

	private $template;

	function getContent()
	{
        // Pretty url stuff
        $arguments = array();
        if (\Tools::REQUEST("arguments")) {
            foreach (explode(",",\Tools::REQUEST("arguments")) as $arg) {
                if (strlen(trim($arg)) > 0)
                    $arguments[] = $arg;
            }
        }
        $section = (\Tools::REQUEST("section"))?:$this->moduleName;
        $action = (count($arguments)>0)?array_shift($arguments):"overview";

        $sectionParts = explode("-", $section);
        $classname = "";
        foreach ($sectionParts as $part) {
            $classname .= ucfirst($part);
        }

        $viewClass = '\\'.$this->moduleName.'\\view\\'.ucfirst($classname);
        if (!class_exists($viewClass))
            $viewClass = '\\'.$this->moduleName.'\\common\\view\\'.ucfirst($classname);

        \AppRoot::debug("view: ".$viewClass);
        if (class_exists($viewClass))
        {
            $view = new $viewClass();
            $method = "get" . ucfirst($action);
            if (!method_exists($view, $method))
                $method = "getOverview";

            \AppRoot::debug("view: ".$viewClass."->".$method."()");
            if (method_exists($view, $method))
                return $view->$method($arguments);
        }


        // oude stuff
		if ($this->moduleSection != null)
			$this->moduleContent = $this->moduleSection->getOverview();

		$tpl = \SmartyTools::getSmarty();
		$tpl->assign("moduleTitle", $this->moduleTitle);
		$tpl->assign("moduleContent", $this->moduleContent);
		return $tpl->fetch("module/index");
	}

	function getTemplate($file)
	{
		if ($dir = \SmartyTools::getTemplateDir($this->moduleName))
			return $dir . $file;
		else
			return "modules/".$this->moduleName."/templates/default/".$file;
	}

	/**
	 * Module enabled?
	 * @return boolean
	 */
	function isEnabled()
	{
		return \AppRoot::config($this->moduleName."enabled");
	}

	/**
	 * Public module?
	 * @return boolean
	 */
	function isPublic()
	{
		return \AppRoot::config($this->moduleName."public");
	}

	/**
	 * Is module available for this user?
	 * @param \users\model\User $user
	 * @return boolean
	 */
	function isAvailable(\users\model\User $user=null)
	{
		if ($user == null)
			$user = \User::getUSER();

		\AppRoot::debug($this->moduleName."->isAvailable(".$user->getFullName().")");

		// Disabled? Dan voor niemand.
		if (!$this->isEnabled())
			return false;

		// Public. Voor iedereen beschikbaar.
		if ($this->isPublic())
			return true;

		// Check authorization groups
		foreach ($user->getAuthGroups() as $authGroup)
		{
			foreach ($authGroup->getModules() as $module)
			{
				if ($module == $this->moduleName)
					return true;
			}
		}

		// Check persoonlijk rechten.
		return $user->hasRight($this->moduleName, "availible");
	}
}
?>