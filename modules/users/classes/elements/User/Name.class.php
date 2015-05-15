<?php
namespace users\elements\User
{
	class Name extends \TextElement\Element
	{
		function getValue()
		{
			$name = "";
			$user = new \users\model\User($this->value);
			if ($user->getMainCharacter() != null)
				$name = "<img src='http://image.eveonline.com/Character/".$user->getMainCharacter()->id."_32.jpg' align='left' style='height: 16px; margin: 0px; border-radius: 2px;'> &nbsp; ";
			$name .= $user->getFullName();
			return $name;
		}
	}
}
?>