<?php
namespace scanning\model
{
	class SystemClass
	{
		public $id = 0;
		public $name;
		public $tag;
		public $color;

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
					$result = \MySQL::getDB()->getRow("SELECT * FROM mapsolarsystemclasses WHERE id = ?", array($this->id));
                    \Cache::file()->set($cacheFileName, json_encode($result));
				}
			}

			if ($result)
			{
				$this->id = $result["id"];
				$this->name = $result["name"];
				$this->tag = $result["tag"];
				$this->color = $result["color"];
			}
		}

        function store()
        {
            $data = [
                "name" => $this->name,
                "tag" => $this->tag,
                "color" => $this->color
            ];
            \MySQL::getDB()->updateinsert("mapsolarsystemclasses", $data, ["id" => $this->id]);
        }
	}
}
?>