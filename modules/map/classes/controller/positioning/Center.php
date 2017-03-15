<?php
namespace map\controller\positioning;

class Center
{
    public $name = "Center";
    public $description = "New systems will expand around the home system";
    public $modifierWidth = 0;
    public $modifierHeight = 0;

    private $origin = null;
    private $chain = null;

    function __construct(\map\model\Map $map)
    {
        $this->setMap($map);
        $this->modifierWidth = \Config::getCONFIG()->get("map_wormhole_width") + (\Config::getCONFIG()->get("map_wormhole_offset_x")*2);
        $this->modifierHeight = \Config::getCONFIG()->get("map_wormhole_height") + (\Config::getCONFIG()->get("map_wormhole_offset_y")*2);
    }

    /**
     * Set chain
     * @param \map\model\Map $map
     */
    function setMap(\map\model\Map $map)
    {
        $this->chain = $map;
    }

    /**
     * Get chain
     * @return \map\model\Map|null
     */
    function getMap()
    {
        return $this->chain;
    }

    /**
     * Set origin
     * @param \map\model\Wormhole $origin
     */
    function setOrigin(\map\model\Wormhole $origin)
    {
        $this->origin = $origin;
    }

    /**
     * Get origin
     * @return \map\model\Wormhole|null
     */
    function getOrigin()
    {
        if (!$this->origin)
            $this->setOrigin($this->getMap()->getHomeWormhole());

        return $this->origin;
    }

    /**
     * Get next position
     * @return array
     */
    function getNextPosition()
    {
        \AppRoot::debug("getNextPosition()");
        $originX = ($this->getOrigin()) ? $this->getOrigin()->x : \Config::getCONFIG()->get("map_wormhole_offset_x");
        $originY = ($this->getOrigin()) ? $this->getOrigin()->y : \Config::getCONFIG()->get("map_wormhole_offset_y");

        $direction = "center";
        $pos = [
            "x" => \Config::getCONFIG()->get("map_wormhole_offset_x"),
            "y" => \Config::getCONFIG()->get("map_wormhole_offset_y")
        ];

        // Probeer systeem voor origin te achterhalen. Die bepaald de richting.
        if ($this->getOrigin() && !$this->getOrigin()->isHomeSystem()) {
            // Fetch terug naar home system
            $route = $this->getOrigin()->getRouteToSystem();
            \AppRoot::debug("route");
            \AppRoot::debug($route);

            if (count($route) >= 2)
            {
                $system1 = $route[0];
                $system2 = $route[1];

                \AppRoot::debug("system1");
                \AppRoot::debug($system1);
                \AppRoot::debug("system2");
                \AppRoot::debug($system2);

                // Bepaal plot richting
                if (isset($system1->x) && isset($system1->y) && isset($system2->x) && isset($system2->y))
                {
                    $offsetX = $system1->x - $system2->x;
                    $offsetY = $system1->y - $system2->y;

                    if ($offsetY > 0)
                        $direction = "down";
                    if ($offsetY < 0)
                        $direction = "up";
                    if ($offsetX > 0)
                        $direction = "right";
                    if ($offsetX < 0)
                        $direction = "left";
                }
            }
        }

        $directionObject = null;
        if ($direction !== null) {
            $directionClass = '\\map\\controller\\positioning\\'.ucfirst($direction);
            if (class_exists($directionClass))
                $directionObject = new $directionClass($this->getMap());
        }
        if ($directionObject == null)
            $directionObject = $this;

        $positions = $directionObject->getPositions();
        $i = 0;
        $positionTaken = null;
        do
        {
            if (isset($positions["x"][$i])) {
                $pos["x"] = $originX + $positions["x"][$i];
                $pos["y"] = $originY + $positions["y"][$i];
                \AppRoot::debug("setPosition: ".$pos["x"]." = ".$originX." + ".$positions["x"][$i]);
            } else {
                // Whatever.. doe wat leuks..
                if ($i%2==0)
                    $pos["x"] += $this->modifierWidth;

                $pos["y"] += $this->modifierHeight;
            }
            $i++;
            $positionTaken = \map\model\Wormhole::findByCoordinates($pos["x"], $pos["y"], $this->getMap());
            \AppRoot::debug("Positie: ".$pos["x"]."x".$pos["y"]);
            if ($positionTaken)
                \AppRoot::debug("<span style='color:red;'>Positie bezet door:</span> ".$positionTaken->name);
        }
        while ($positionTaken !== null);

        // Return positie
        return $pos;
    }

    function getPositions($position=array())
    {
        // Recht naar rechts
        $position["x"][] = $this->modifierWidth;
        $position["y"][] = 0;

        // Recht naar links
        $position["x"][] = $this->modifierWidth * -1;
        $position["y"][] = 0;

        // Recht naar beneden
        $position["x"][] = 0;
        $position["y"][] = $this->modifierHeight;

        // Recht naar boven
        $position["x"][] = 0;
        $position["y"][] = $this->modifierHeight * -1;

        // Schuin naar rechts en beneden
        $position["x"][] = $this->modifierWidth;
        $position["y"][] = $this->modifierHeight;

        // Schuin naar links en beneden
        $position["x"][] = $this->modifierWidth * -1;
        $position["y"][] = $this->modifierHeight;

        // Schuin naar rechts en boven
        $position["x"][] = $this->modifierWidth;
        $position["y"][] = $this->modifierHeight * -1;

        // Schuin naar links en boven
        $position["x"][] = $this->modifierWidth * -1;
        $position["y"][] = $this->modifierHeight * -1;




        // Schuin naar boven-boven en naar rechts.
        $position["x"][] = $this->modifierWidth;
        $position["y"][] = -1 * ($this->modifierHeight*2);

        // Schuin naar beneden-beneden en naar rechts.
        $position["x"][] = $this->modifierWidth;
        $position["y"][] = ($this->modifierHeight*2);

        // Schuin naar boven-boven en naar links.
        $position["x"][] = -1 * $this->modifierWidth;
        $position["y"][] = -1 * ($this->modifierHeight*2);

        // Schuin naar beneden-beneden en naar links.
        $position["x"][] = -1 * $this->modifierWidth;
        $position["y"][] = ($this->modifierHeight*2);

        // Schuin naar boven-boven en naar rechts-rechts.
        $position["x"][] = ($this->modifierWidth);
        $position["y"][] = -1 * ($this->modifierHeight*3);

        // Schuin naar beneden-beneden en naar rechts.
        $position["x"][] = ($this->modifierWidth);
        $position["y"][] = ($this->modifierHeight*3);

        // Schuin naar boven-boven en naar links-links.
        $position["x"][] = -1 * ($this->modifierWidth);
        $position["y"][] = -1 * ($this->modifierHeight*3);

        // Schuin naar beneden-beneden en naar links-links.
        $position["x"][] = -1 * ($this->modifierWidth);
        $position["y"][] = ($this->modifierHeight*3);

        // Schuin naar boven-boven-boven en naar rechts-rechts.
        $position["x"][] = ($this->modifierWidth*2);
        $position["y"][] = -1 * ($this->modifierHeight);

        // Schuin naar beneden-beneden-beneden en naar rechts.
        $position["x"][] = ($this->modifierWidth*2);
        $position["y"][] = ($this->modifierHeight);

        // Schuin naar boven-boven-boven en naar links-links.
        $position["x"][] = -1 * ($this->modifierWidth*2);
        $position["y"][] = -1 * ($this->modifierHeight);

        // Schuin naar beneden-beneden-beneden en naar links-links.
        $position["x"][] = -1 * ($this->modifierWidth*2);
        $position["y"][] = ($this->modifierHeight);


        return $position;
    }
}