<?php
namespace eve\elements
{
	class SolarSystem extends \TextElement\Element
	{
		function setValue($value)
		{
			if ($this->obligatory && strlen(trim($value)) == 0)
				return false;

			if (strlen(trim($value)) == 0)
			{
				$this->value = 0;
				return true;
			}
			else
			{
				$solarSystemController = new \eve\controller\SolarSystem();
				if ($solarSystem = $solarSystemController->getSolarsystemByName($value))
				{
					$this->value = $solarSystem->id;
					return true;
				}
				else
				{
					$this->errors[] = "Solarsystem `".$value."` not found.";
					$this->value = 0;
					return false;
				}
			}
		}

		function getValue()
		{
			$system = new \eve\model\SolarSystem($this->value);
			return $system->name;
		}

		function getAddValue()
		{
			return $this->value;
		}

		function getEditValue()
		{
			$system = new \eve\model\SolarSystem($this->value);
			return $system->name;
		}
	}
}
?>