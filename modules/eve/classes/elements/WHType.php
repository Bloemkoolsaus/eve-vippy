<?php
namespace eve\elements
{
	class WHType extends \AutoCompleteElement\Element
	{
		public $keyfield = "id";
		public $namefield = "name";
		public $table = "alliances";

		function getEditHTML($extraAttributes=array())
		{
			$extraAttributes = array_merge($extraAttributes, array("element" => "eve-elements-WHType"));
			return parent::getEditHTML($extraAttributes);
		}

		public static function getValues()
		{
            $term = strtoupper(\MySQL::escape(\Tools::REQUEST("term")));

            $types = array();
            if ($results = \MySQL::getDB()->getRows("SELECT t.id, t.name, sc.tag
													FROM 	mapwormholetypes t
														INNER JOIN mapsolarsystemclasses sc ON sc.id = t.destination
													WHERE   t.name LIKE '".$term."%'
													GROUP BY t.id
													ORDER BY t.name"))
            {
                foreach ($results as $result) {
                    $types[] = ["id" => $result["id"], "label" => $result["name"]];
                }
            }

			return json_encode($types);
		}
	}
}
?>