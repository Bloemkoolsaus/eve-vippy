<?php
namespace users
{
	class User extends \users\model\User
	{
		public static function getPasswordResetForm()
		{
			$user = new User(\Tools::REQUEST("id"));
			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("userid", $user->id);
			$tpl->assign("username", $user->displayname);
			return $tpl->fetch(\SmartyTools::getTemplateDir("users")."pwreset.html");
		}

		public static function getBanUserForm()
		{
			$user = new User(\Tools::REQUEST("id"));
			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("userid", $user->id);
			$tpl->assign("username", $user->displayname);
			$tpl->assign("userdeleted", ($user->deleted)?1:0);
			return $tpl->fetch(\SmartyTools::getTemplateDir("users")."ban.html");
		}
	}
}
?>