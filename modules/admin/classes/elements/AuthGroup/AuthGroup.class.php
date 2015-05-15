<?php
namespace admin\elements\AuthGroup
{
	class AuthGroup extends \TextElement\Element
	{
		function getValue($dbvalue=false)
		{
			$authGroup = new \admin\model\AuthGroup($this->value);
			return $authGroup->name;
		}
	}
}
?>