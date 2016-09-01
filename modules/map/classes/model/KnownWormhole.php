<?php
namespace map\model;

class KnownWormhole extends \Model
{
    protected $_table = "map_knownwormhole";
    protected $_keyfield = ["solarsystemid","authgroupid"];

    public $solarSystemID;
    public $authGroupID;
    public $name;
    public $status = 0;

    private $_SolarSystem = null;
    private $_AuthGroup = null;

    function getColor()
    {
        if ($this->status > 1)
            return "#4499FF";
        if ($this->status == 1)
            return "#88AAFF";
        if ($this->status == -1)
            return "#FF5555";
        if ($this->status < -1)
            return "#CC0000";

        return "none";
    }

    function getStatus()
    {
        if ($this->status > 1)
            return "Alliance System";
        if ($this->status == 1)
            return "Friendly System";
        if ($this->status == -1)
            return "Hostile System";
        if ($this->status < -1)
            return "Dangerous System";

        return "Neutral System";
    }

    function getIcon()
    {
        if ($this->status > 1)
            return "images/eve/standing.alliance.png";
        if ($this->status == 1)
            return "images/eve/standing.blue.png";
        if ($this->status == -1)
            return "images/eve/standing.red.png";
        if ($this->status < -1)
            return "images/eve/standing.war.png";

        return "images/eve/standing.neutral.png";
    }

    /**
     * Get solarsystem
     * @return \map\model\SolarSystem
     */
    function getSolarSystem()
    {
        if ($this->_SolarSystem === null)
            $this->_SolarSystem = new \map\model\SolarSystem($this->solarSystemID);

        return $this->_SolarSystem;
    }

    /**
     * Get authorization group
     * @return \admin\model\AuthGroup|null
     */
    function getAuthGroup()
    {
        if ($this->_AuthGroup === null)
            $this->_AuthGroup = new \admin\model\AuthGroup($this->authGroupID);

        return $this->_AuthGroup;
    }


    /**
     * Find known wormhole by solarsystemid
     * @param $solarSystemID
     * @return \map\model\KnownWormhole|null
     */
    public static function findBySolarSystemID($solarSystemID)
    {
        $authGroupID = \User::getUSER()->getCurrentAuthGroupID();
        return self::findOne(["solarsystemid" => $solarSystemID, "authgroupid" => $authGroupID]);
    }
}