<?php
namespace users\elements
{
	Class User extends \TextElement\Element
	{
		function getValue()
		{
			$user = new \users\model\User($this->value);
			return $user->getFullName();
		}
	}
}
?>