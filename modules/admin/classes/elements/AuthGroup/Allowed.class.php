<?php
namespace admin\elements\AuthGroup
{
	class Allowed extends \BooleanElement\Element
	{
		function getValue($dbvalue=false)
		{
			$authGroup = new \admin\model\AuthGroup($this->value);
			$this->value = $authGroup->isAllowed();
			return parent::getValue(false);
		}
	}
}
?>