<?php
namespace eve\model;

class Corporation
{
    public $id = 0;
    public $ticker;
    public $name;
    public $ceoID = 0;
    public $allianceID = 0;
    public $updateDate = null;

    private $alliance = null;

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
            $result = \MySQL::getDB()->getRow("SELECT * FROM corporations WHERE id = ?", array($this->id));

        if ($result)
        {
            $this->id = $result["id"];
            $this->ticker = $result["ticker"];
            $this->name = $result["name"];
            $this->ceoID = $result["ceo"]-0;
            $this->allianceID = $result["allianceid"]-0;
            $this->updateDate = $result["updatedate"];
        }
    }

    function store()
    {
        if (!$this->id)
            return false;
        if (!$this->name)
            return false;

        $data = [
            "id" => $this->id,
            "ticker" => $this->ticker,
            "name" => $this->name,
            "ceo" => $this->ceoID,
            "allianceid" => $this->allianceID,
            "updatedate" => date("Y-m-d H:i:s")
        ];
        \MySQL::getDB()->updateinsert("corporations", $data, ["id" => $this->id]);

        // Update CEO, maar alleen als de CEO toon ook echt nog in die corp zit!!
        \MySQL::getDB()->doQuery("update characters set isceo = 0 where corpid = ?", [$this->id]);
        \MySQL::getDB()->doQuery("update characters set isceo = 1 where id = ? and corpid = ?", [$this->ceoID, $this->id]);
        return true;
    }

    /**
     * Get alliance.
     * @return \eve\model\Alliance|null
     */
    function getAlliance()
    {
        if ($this->alliance == null && $this->allianceID > 0)
            $this->alliance = new \eve\model\Alliance($this->allianceID);

        return $this->alliance;
    }

    /**
     * is npc corp?
     * @return bool
     */
    function isNPC()
    {
        if ($this->id < 1100000)
            return true;

        return false;
    }





    /**
     * Find all
     * @param array $conditions
     * @return \eve\model\Corporation[]
     */
    public static function findAll($conditions=[])
    {
        $query = [];
        $params = [];
        foreach ($conditions as $var => $val) {
            $query[] = $var." = ?";
            $params[] = $val;
        }

        $corporations = [];
        if ($results = \MySQL::getDB()->getRows("select * from corporations ".((count($query)>0)?"where ".implode(" and ", $query):"")." order by name", $params)) {
            foreach ($results as $result) {
                $corp = new static();
                $corp->load($result);
                $corporations[] = $corp;
            }
        }
        return $corporations;
    }

    /**
     * Find one
     * @param array $conditions
     * @return Corporation|null
     */
    public static function findOne($conditions=[])
    {
        $corporations = static::findAll($conditions);
        if (count($corporations) > 0)
            return $corporations[0];

        return null;
    }

    /**
     * Find character by ID
     * @param $corporationID
     * @return \eve\model\Corporation|null
     */
    public static function findByID($corporationID)
    {
        if ($result = \MySQL::getDB()->getRow("select * from corporations where id = ?", [$corporationID])) {
            $corp = new static();
            $corp->load($result);
            return $corp;
        }
        return null;
    }

    /**
     * Get corporation by id
     * @param integer $corporationID
     * @return \eve\model\Corporation|NULL
     * @deprecated
     */
    public static function getCorporationByID($corporationID)
    {
        return self::findByID($corporationID);
    }

    /**
     * Get corporations by alliance
     * @param integer $allianceID
     * @return \eve\model\Corporation[]
     */
    public static function getCorporationsByAlliance($allianceID)
    {
        return self::findAll(["allianceid" => $allianceID]);
    }
}