<?php
namespace scanning\controller\map\positioning
{
	class Down extends \scanning\controller\map\positioning\Center
	{
		public $name = "Left";
		public $description = "New systems will expand left of the home system";

		function getPositions($position=array())
		{
			$position = array();

			// Recht naar beneden
			$position["x"][] = 0;
			$position["y"][] = $this->modifierHeight;

			// Recht naar beneden en rechts
			$position["x"][] = $this->modifierWidth;
			$position["y"][] = $this->modifierHeight;

			// Recht naar beneden en links
			$position["x"][] = $this->modifierWidth*-1;
			$position["y"][] = $this->modifierHeight;

			// Recht naar rechts
			$position["x"][] = $this->modifierWidth;
			$position["y"][] = 0;

			// Recht naar links
			$position["x"][] = $this->modifierWidth*-1;
			$position["y"][] = 0;

			// Recht naar beneden en rechts
			$position["x"][] = $this->modifierWidth;
			$position["y"][] = $this->modifierHeight*2;

			// Recht naar beneden en links
			$position["x"][] = $this->modifierWidth*-1;
			$position["y"][] = $this->modifierHeight*2;




			return parent::getPositions($position);
		}
	}
}
?>