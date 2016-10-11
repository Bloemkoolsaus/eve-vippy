<?php
namespace users\elements\User
{
	class Status extends \TextElement\Element
	{
		function getValue()
		{
			$user = new \users\model\User($this->value);

			$icon = "/images/eve/apply.png";
			$color = "55ff55";
			$status = "Active";

			if ($user->deleted)
			{
				$icon = "/images/eve/skull.red.png";
				$color = "ff1111";
				$status = "Banned";
			}
			else if (!$user->isAuthorized())
			{
				$icon = "/images/eve/cross.png";
				$color = "ffaa11";
				$status = "Un-Authorized";
			}


			return "<img src='".$icon."' align='left'> &nbsp; <span style='color: #".$color."'>".$status."</span>";
		}
	}
}
?>