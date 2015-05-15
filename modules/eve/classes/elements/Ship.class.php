<?php
namespace eve\elements
{
	class Ship extends \SelectElement\Element
	{
		public $keyfield = "typeid";
		public $namefield = "typename";
		public $table = "invtypes";
		public $orderbyfield = "typename";
		public $whereQuery = null;

		public $showLogo = false;
		public $showType = false;

		function __construct($title, $field=false)
		{
			$this->table = \eve\Module::eveDB().".invtypes";
			$this->whereQuery = "WHERE groupid IN (SELECT groupid FROM ".\eve\Module::eveDB().".invgroups WHERE categoryid = 6)";
			parent::__construct($title, $field);
		}

		function getValue()
		{
			$ship = new \eve\model\Ship($this->value);
			$html = $ship->name;

			if ($this->showType && $ship->getShipType() != $ship->name)
				$html .= " - <i>".$ship->getShipType()."</i>";

			if ($this->showLogo)
				$html .= "<img src='https://image.eveonline.com/Render/".$ship->id."_32.png' style='height: 20px; margin: -2px; margin-right: 6px; border-radius: 3px;' align='left'/>";

			return $html;
		}
	}
}
?>