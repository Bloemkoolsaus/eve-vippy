<?php
namespace eve\elements
{
	class Character extends \TextElement\Element
	{
		function getValue()
		{
			$character = new \eve\model\Character($this->value);
			return $character->name;
		}
	}
}
?>