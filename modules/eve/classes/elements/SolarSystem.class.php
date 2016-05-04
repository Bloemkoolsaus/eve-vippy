<?php
namespace eve\elements
{
	class SolarSystem extends \AutoCompleteElement\Element
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

		function getEditHTML($extraAttributes=array())
		{
			$extraAttributes = array_merge($extraAttributes, array("element" => "eve-elements-SolarSystem"));
			return parent::getEditHTML($extraAttributes);
		}

		public static function getValues()
		{
			$query = array();

			$searchMinLength = \Tools::Escape(\Tools::REQUEST("minsearchlen"))-0;
			if (\Tools::Escape(\Tools::REQUEST("term")))
			{
				foreach (explode(" ", \Tools::Escape(\Tools::REQUEST("term"))) as $term) {
					if (strlen(trim($term)) >= $searchMinLength)
						$query[] = "solarsystemname LIKE '%".\MySQL::escape($term)."%'";
				}
			}

			$results = array();
			if (count($query) > 0)
			{
				if ($records = \MySQL::getDB()->getRows("SELECT *
													FROM	".\eve\Module::eveDB().".mapsolarsystems
													WHERE	".implode(" AND ", $query)."
													ORDER BY solarsystemname "))
                {
                    foreach ($records as $record)
                    {
                        $system = new \eve\model\SolarSystem();
                        $system->load($record);

                        $results[] = [
                            "id" => $system->id,
                            "label" => $system->name." (".$system->getClass(true)." - ".$system->getRegion()->name.")"
                        ];
                    }
                }
			}
			return json_encode($results);
		}
	}
}
?>