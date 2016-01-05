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

		function isK162()
		{
			if ($this->id == 0)
				return true;
			if ($this->id == 9999)
				return true;

			return false;
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
	}
}
?>