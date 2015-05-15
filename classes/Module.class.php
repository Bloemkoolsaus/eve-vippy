<?php
class Module
{
	public $moduleName = "module";
	public $moduleTitle = "Module";
	public $moduleContent = "";
	public $moduleSection = null;

	private $template;

	function __construct()
	{

	}

	function getContent()
	{
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