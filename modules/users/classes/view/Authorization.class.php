<?php
namespace users\view
{
	class Authorization
	{
		function getNotAuthorizedView()
		{
			$tpl = \SmartyTools::getSmarty();
			return $tpl->fetch("users/authorization/notauthorized");
		}

		function getNoApiKeysView()
		{
			$tpl = \SmartyTools::getSmarty();
			return $tpl->fetch("users/authorization/noapikeys");
		}
	}
}
?>