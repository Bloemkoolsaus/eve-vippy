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

		public static function getAuthorizeForm()
		{
			$user = new User(\Tools::REQUEST("id"));

            $authgroups = [];
            if (\User::getUSER()->getIsSysAdmin())
                $authgroups = \admin\model\AuthGroup::getAuthGroups();
            else {
                foreach (\User::getUSER()->getAuthGroups() as $group) {
                    if ($group->getMayAdmin(\User::getUSER()))
                        $authgroups[] = $group;
                }
            }


			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("user", $user);
            $tpl->assign("authgroups", $authgroups);
			return $tpl->fetch(\SmartyTools::getTemplateDir("users")."authorize.html");
		}
	}
}
?>