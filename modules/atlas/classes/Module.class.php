<?php
namespace atlas
{
	class Module extends \Module
	{
		public $moduleName = "atlas";
		public $moduleTitle = "ATLAS";

		function getContent()
		{
			foreach (\User::getUSER()->getAuthGroups() as $authGroup) {
				\AppRoot::redirect($authGroup->getConfig("atlas_url"),false);
			}
		}

        /**
         * (non-PHPdoc)
         * @see Module::isAvailable()
         * @param \users\model\User $user
         * @return bool
         */
		function isAvailable(\users\model\User $user=null)
		{
			if ($user == null)
				$user = \User::getUSER();

			\AppRoot::debug($this->moduleName."->isAvailable(".$user->getFullName().")");

			foreach ($user->getAuthGroups() as $authGroup)
			{
				if ($authGroup->getConfig("atlas_url"))
					return parent::isAvailable($user);
			}

			\AppRoot::debug("NO URL CONFIG");
			return false;
		}
	}
}
?>