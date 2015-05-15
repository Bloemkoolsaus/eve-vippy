<?php
namespace scanning\controller\map\positioning
{
	class Right extends \scanning\controller\map\positioning\Center
	{
		public $name = "Right";
		public $description = "New systems will expand right of the home system";

		function getPositions($position=array())
		{
			$position = array();

			// Recht naar rechts
			$position["x"][] = $this->modifierWidth;
			$position["y"][] = 0;

			// Recht naar rechts en beneden
			$position["x"][] = $this->modifierWidth;
			$position["y"][] = $this->modifierHeight;

			// Recht naar rechts en boven
			$position["x"][] = $this->modifierWidth;
			$position["y"][] = $this->modifierHeight * -1;

			// Recht naar beneden
			$position["x"][] = 0;
			$position["y"][] = $this->modifierHeight;

			// Recht naar boven
			$position["x"][] = 0;
			$position["y"][] = $this->modifierHeight * -1;

			// Recht naar rechts en beneden
			$position["x"][] = $this->modifierWidth;
			$position["y"][] = $this->modifierHeight * 2;

			// Recht naar rechts en boven
			$position["x"][] = $this->modifierWidth;
			$position["y"][] = ($this->modifierHeight * 2) * -1;




			return parent::getPositions($position);
		}
	}
}
?>