<?php
namespace users\elements\User
{
	class Corporation extends \TextElement\Element
	{
		function getValue()
		{
			$user = new \users\model\User($this->value);
			if ($user->getMainCharacter() !== null)
			{

				$corpname = "<img src='http://image.eveonline.com/Corporation/".$user->getMainCharacter()->corporationID."_32.png' align='left' style='height: 16px; margin: 0px; border-radius: 2px;'> &nbsp; ";
				$corpname .= $user->getMainCharacter()->getCorporation()->name;
				if ($user->getMainCharacter()->getCorporation()->getAlliance() != null)
					$corpname .= " &nbsp; (<i>".$user->getMainCharacter()->getCorporation()->getAlliance()->name."</i>)";

				return $corpname;
			}

			return "";
		}
	}
}
?>