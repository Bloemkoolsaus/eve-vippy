<?php
namespace scanning\controller\map\positioning
{
	class Center
	{
		public $name = "Center";
		public $description = "New systems will expand around the home system";
		public $modifierWidth = 0;
		public $modifierHeight = 0;

		private $origin = null;
		private $chain = null;

		function __construct(\scanning\model\Chain $chain=null)
		{
			if ($chain != null)
				$this->setChain($chain);

			$this->modifierWidth = \scanning\Wormhole::$defaultWidth + (\scanning\Wormhole::$defaultOffset*2);
			$this->modifierHeight = \scanning\Wormhole::$defaultHeight + (\scanning\Wormhole::$defaultOffset*2);
		}

		/**
		 * Set chain
		 * @param \scanning\model\Chain $chain
		 */
		function setChain(\scanning\model\Chain $chain)
		{
			$this->chain = $chain;
		}

		/**
		 * Get chain
		 * @return \scanning\model\Chain
		 */
		function getChain()
		{
			if ($this->chain == null)
				$this->chain = \scanning\model\Chain::getCurrentChain();

			return $this->chain;
		}

		/**
		 * Set origin
		 * @param \scanning\model\Wormhole $origin
		 */
		function setOrigin(\scanning\model\Wormhole $origin)
		{
			$this->origin = $origin;
		}

        /**
         * Get origin
         * @return \scanning\model\Wormhole|null
         */
        function getOrigin()
        {
            return $this->origin;
        }

        /**
         * Get next position
         * @return array
         */
		function getNextPosition()
		{
			\AppRoot::debug("getNextPosition()");
			$originX = ($this->getOrigin() !== null) ? $this->getOrigin()->x : 50;
			$originY = ($this->getOrigin() !== null) ? $this->getOrigin()->y : 50;

			$direction = null;
			$pos = array("x" => 50, "y" => 50);


			// Probeer systeem voor origin te achterhalen. Die bepaald de richting.
			if ($this->origin != null)
			{
				if (!$this->getOrigin()->isHomeSystem())
				{
					// Fetch terug naar home system
					$route = $this->getOrigin()->getRouteToSystem();
					if (count($route) >= 2)
					{
						$system1 = \scanning\model\Wormhole::getWormholeBySystemID($route[0]);
						$system2 = \scanning\model\Wormhole::getWormholeBySystemID($route[1]);

						// Bepaal plot richting
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
			if ($direction !== null)
			{
				$directionClass = '\\scanning\\controller\\map\\positioning\\'.ucfirst($direction);
				if (class_exists($directionClass))
					$directionObject = new $directionClass();
			}
			if ($directionObject == null)
				$directionObject = $this;

			$positions = $directionObject->getPositions();
			$i = 0;
			do
			{
				if (isset($positions["x"][$i]))
				{
					$pos["x"] = $originX + $positions["x"][$i];
					$pos["y"] = $originY + $positions["y"][$i];
				}
				else
				{
					// Whatever.. doe wat leuks..
					if ($i%2==0)
						$pos["x"] += $this->modifierWidth;

					$pos["y"] += $this->modifierHeight;
				}
				$i++;
			}
			while (\scanning\Wormhole::getWormholeByCoordinates($pos["x"], $pos["y"], $this->getChain()->id) !== false);

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
}
?>