<?php
namespace scanning\controller\map\positioning
{
	class Up extends \scanning\controller\map\positioning\Center
	{
		public $name = "Left";
		public $description = "New systems will expand left of the home system";

		function getPositions($position=array())
		{
			$position = array();

			// Recht naar boven
			$position["x"][] = 0;
			$position["y"][] = $this->modifierHeight * -1;

			// Recht naar boven en rechts
			$position["x"][] = $this->modifierWidth;
			$position["y"][] = $this->modifierHeight * -1;

			// Recht naar boven en links
			$position["x"][] = $this->modifierWidth*-1;
			$position["y"][] = $this->modifierHeight * -1;

			// Recht naar rechts
			$position["x"][] = $this->modifierWidth;
			$position["y"][] = 0;

			// Recht naar links
			$position["x"][] = $this->modifierWidth*-1;
			$position["y"][] = 0;

			// Recht naar boven en rechts
			$position["x"][] = $this->modifierWidth;
			$position["y"][] = ($this->modifierHeight*2) * -1;

			// Recht naar boven en links
			$position["x"][] = $this->modifierWidth*-1;
			$position["y"][] = ($this->modifierHeight*2) * -1;




			return parent::getPositions($position);
		}
	}
}
?>