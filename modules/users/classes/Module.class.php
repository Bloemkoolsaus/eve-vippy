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
			$action = (\Tools::REQUEST("action"))?:"overview";

			if (\Tools::REQUEST("loginas"))
			{
				if (\User::getUSER()->getIsSysAdmin())
				{
					\User::getUSER()->logout();
					$user = new \users\model\User(\Tools::REQUEST("loginas"));
					$user->setLoginStatus();
				}
				\AppRoot::redirect("index.php?module=profile&section=chars");
			}

			if ($section == "logs")
			{
				if (!\User::getUSER()->isAdmin())
					\AppRoot::redirect(APP_URL);

				$logView = new \users\view\Log();
				return $logView->getOverview();
			}

            if ($section == "users")
            {
                if (\User::getUSER()->isAdmin() || \User::getUSER()->hasRight("users", "manageusers"))
                {
                    $userController = new \users\controller\User();

                    if ($action == "resetpwform")
                        return User::getPasswordResetForm();
                    else if ($action == "banform")
                        return User::getBanUserForm();
                    else if ($action == "authorizeform")
                        return User::getAuthorizeForm();
                    else if ($action == "showlog")
                    {
                        $log = new \users\view\Log();
                        return $log->showUserLog(\Tools::REQUEST("id"));
                    }
                    else if ($action == "edit")
                    {
                        $user = new \users\model\User(\Tools::REQUEST("id"));
                        \AppRoot::title($user->getFullName());
                        return $userController->getEditForm(\Tools::REQUEST("id"));
                    }
                    else
                        $this->moduleSection = $userController->getOverviewSection();
                }
                else
                    return \Tools::noRightMessage();
            }

            if ($section == "usergroups")
            {
                if (\User::getUSER()->isAdmin() || \User::getUSER()->hasRight("users", "managegroups"))
                {
                    $controller = new \users\controller\UserGroup();
                    if ($action == "edit" || $action == "new")
                        return $controller->getEditForm(\Tools::REQUEST("id"));
                    else
                    {
                        $this->moduleTitle = "Usergroups";
                        $this->moduleSection = $controller->getOverviewSection();
                    }
                }
                else
                    return \Tools::noRightMessage();
            }

			return parent::getContent();
		}

		function getAppData(\stdClass $appData)
		{
			if (\User::getUSER()->loggedIn())
				$appData->user = \User::getUSER();

			return $appData;
		}
	}
}
?>