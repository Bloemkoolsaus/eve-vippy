<?php
namespace map\controller\positioning;

class Left extends \map\controller\positioning\Center
{
    public $name = "Left";
    public $description = "New systems will expand left of the home system";

    function getPositions($position=array())
    {
        $position = array();

        // Recht naar links
        $position["x"][] = $this->modifierWidth * -1;
        $position["y"][] = 0;

        // Recht naar links en beneden
        $position["x"][] = $this->modifierWidth * -1;
        $position["y"][] = $this->modifierHeight;

        // Recht naar links en boven
        $position["x"][] = $this->modifierWidth * -1;
        $position["y"][] = $this->modifierHeight * -1;

        // Recht naar beneden
        $position["x"][] = 0;
        $position["y"][] = $this->modifierHeight;

        // Recht naar boven
        $position["x"][] = 0;
        $position["y"][] = $this->modifierHeight * -1;

        // Recht naar links en beneden
        $position["x"][] = $this->modifierWidth * -1;
        $position["y"][] = $this->modifierHeight * 2;

        // Recht naar links en boven
        $position["x"][] = $this->modifierWidth * -1;
        $position["y"][] = ($this->modifierHeight * 2) * -1;




        return parent::getPositions($position);
    }
}