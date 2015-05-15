<?php
namespace admin\elements\AuthGroup
{
	class ValidUsers extends \TextElement\Element
	{
		function getValue($dbvalue=false)
		{
			$authGroup = new \admin\model\AuthGroup($this->value);
			if (count($authGroup->getAllowedUsers()) == 0)
				return " ";

			return "<div style='text-align: center;'>".count($authGroup->getAllowedUsers())."</div>";
		}
	}
}
?>