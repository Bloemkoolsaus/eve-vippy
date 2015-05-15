<?php
namespace users\elements\User
{
	class LastLogin extends \TextElement\Element
	{
		function getValue()
		{
			$user = new \users\model\User($this->value);
			$lastLogin = $user->getLastLogin();
			if ($lastLogin)
			{
				if (strlen(trim($lastLogin["logdate"])) > 0)
					return \Tools::getFullDate($lastLogin["logdate"]);
			}

			return "";
		}
	}
}
?>