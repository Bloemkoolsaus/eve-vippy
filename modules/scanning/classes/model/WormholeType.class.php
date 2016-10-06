<?php
namespace scanning\model
{
	class WormholeType
	{
		public $id = 0;
		public $name;
		public $type;
		public $destination;
		public $lifetime;
		public $jumpmass;
		public $maxmass;

        private $destinationClass;

		function __construct($id=false)
		{
			if ($id) {
				$this->id = $id;
				$this->load();
			}
		}

		function load($result=false)
		{
			if (!$result)
			{
				$cacheFileName = "whtype/".$this->id.".json";
				if ($cache = \Cache::file()->get($cacheFileName))
					$result = json_decode($cache, true);
				else
				{
					$result = \MySQL::getDB()->getRow("SELECT * FROM mapwormholetypes WHERE id = ?", array($this->id));
                    \Cache::file()->set($cacheFileName, json_encode($result));
				}
			}

			if ($result)
			{
				$this->id = $result["id"];
				$this->name = $result["name"];
				$this->type = $result["whtype"];
				$this->destination = $result["destination"];
				$this->lifetime = $result["lifetime"];
				$this->jumpmass = $result["jumpmass"];
				$this->maxmass = $result["maxmass"];
			}
		}

        function store()
        {
            $data = [
                "name" => $this->name,
                "whtype" => $this->type,
                "destination" => $this->destination,
                "lifetime" => $this->lifetime,
                "jumpmass" => $this->jumpmass,
                "maxmass" => $this->maxmass
            ];
            \MySQL::getDB()->updateinsert("mapwormholetypes", $data, ["id" => $this->id]);
        }

		function isK162()
		{
			if ($this->id == 0)
				return true;
			if ($this->id == 9999)
				return true;

			return false;
		}

        function goesToWspace()
        {
            if ($this->destination >= 1 && $this->destination <= 3)
                return true;

            return false;
        }

        function goesToKspace()
        {
            if ($this->goesToWspace())
                return false;

            return true;
        }

        function isHighsec()
        {
            if ($this->destination == 1)
                return true;

            return false;
        }
        function isLowsec()
        {
            if ($this->destination == 2)
                return true;

            return false;
        }
        function isNullsec()
        {
            if ($this->destination == 3)
                return true;

            return false;
        }


        /**
         * Get destination class
         * @return \scanning\model\SystemClass
         */
        function getDestinationclass()
        {
            if ($this->destinationClass == null)
                $this->destinationClass = new \scanning\model\SystemClass($this->destination);

            return $this->destinationClass;
        }




        /**
         * Find by name
         * @param $name
         * @return \scanning\model\WormholeType|null
         */
        public static function findByName($name)
        {
            if ($result = \MySQL::getDB()->getRow("SELECT * FROM mapwormholetypes WHERE name = ?", [strtoupper($name)]))
            {
                $type = new \scanning\model\WormholeType();
                $type->load($result);
                return $type;
            }

            return null;
        }

        /**
         * Find by solarsystem
         * @param $solarSystemID
         * @return \scanning\model\WormholeType[]
         */
        public static function findBySystemID($solarSystemID)
        {
            $system = new \scanning\model\System($solarSystemID);
            $whTypeID = $system->getClassID();

            $whTypes = [];
            if ($results = \MySQL::getDB()->getRows("select t.*
                                                    from   mapwormholetypes t
                                                        left join mapwormholetypespawns s on s.whtypeid = t.id
                                                    where s.fromclass = ?
                                                    group by t.id order by t.name"
                                            , [$whTypeID]))
            {
                foreach ($results as $result)
                {
                    $type = new \scanning\model\WormholeType();
                    $type->load($result);
                    $whTypes[] = $type;
                }
            }

            return $whTypes;
        }

        /**
         * Find by solarsystem
         * @return \scanning\model\WormholeType[]
         */
        public static function findAll()
        {
            $whTypes = [];
            if ($results = \MySQL::getDB()->getRows("select t.*
                                                    from   mapwormholetypes t
                                                        left join mapwormholetypespawns s on s.whtypeid = t.id
                                                    group by t.id order by t.name"))
            {
                foreach ($results as $result)
                {
                    $type = new \scanning\model\WormholeType();
                    $type->load($result);
                    $whTypes[] = $type;
                }
            }

            return $whTypes;
        }
	}
}
?>