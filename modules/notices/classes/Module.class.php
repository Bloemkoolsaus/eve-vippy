<?php
namespace notices
{
	class Module extends \Module
	{
		public $moduleName = "notices";
		public $moduleTitle = "Notifications";
        public $public = false;


		function getContent()
		{
			$section = (\Tools::REQUEST("section")) ? \Tools::REQUEST("section") : "overview";
			$action = (\Tools::REQUEST("action")) ? \Tools::REQUEST("action") : "";

			if ($section == "fetch")
			{
				if ($action == "markread")
				{
					$notice = new \notices\model\Notice(\Tools::REQUEST("id"));
					$notice->markRead();
					return true;
				}
			}

			if ($section == "map")
			{
				$notice = new \notices\controller\Notice();
				if ($action == "new")
					return $notice->getNewForm();
			}

			if ($section == "overview")
			{
				$notice = new \notices\controller\Notice();
				$this->moduleTitle = "Notifications";
				$this->moduleSection = $notice->getOverviewSection();
			}

			return parent::getContent();
		}
	}
}
?>