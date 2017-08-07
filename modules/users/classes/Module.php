<?php
namespace users
{
	class Module extends \Module
	{
		public $moduleName = "users";
		public $moduleTitle = "Users";

		function getContent()
		{
			$section = (\Tools::REQUEST("section"))?:"users";
            \AppRoot::title("Users");

			if (\Tools::REQUEST("loginas")) {
				if (\User::getUSER() && \User::getUSER()->getIsSysAdmin()) {
					\User::getUSER()->logout();
					$user = new \users\model\User(\Tools::REQUEST("loginas"));
					$user->setLoginStatus();
				}
				\AppRoot::redirect("");
			}

			if ($section == "logs") {
				if (!\User::getUSER()->isAdmin())
					\AppRoot::redirect("");

				$logView = new \users\view\Log();
				return $logView->getOverview();
			}

			return parent::getContent();
		}

		function getView($checkAuth=true)
        {
            $checkAuth = (\Tools::REQUEST("section")=="login")?false:$checkAuth;
            return parent::getView($checkAuth);
        }

        function getAppData(\stdClass $appData)
		{
			if (\User::getUSER())
				$appData->user = \User::getUSER();

			return $appData;
		}
	}
}