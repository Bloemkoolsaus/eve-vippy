<?php
namespace map\model;

class ClosestSystem extends \Model
{
    protected $_table = "map_closest_systems";
    protected $_keyfield = ["solarsystemid","authgroupid","userid"];

    public $solarSystemID;
    public $authGroupID = 0;
    public $userID = 0;
    public $showOnMap = false;

    private $_solarSystem = null;

    /**
     * get solar system
     * @return \eve\model\SolarSystem|null
     */
    function getSolarSystem()
    {
        if ($this->_solarSystem === null)
            $this->_solarSystem = new \eve\model\SolarSystem($this->solarSystemID);

        return $this->_solarSystem;
    }


    /**
     * Get closes systems
     * @return \map\model\ClosestSystem[]
     */
    public static function getClosestSystemsBySystemID()
    {
        $systems = [];

        // user defined
        foreach (self::findAll(["userid" => \User::getUSER()->id]) as $system) {
            $systems[$system->solarSystemID] = $system;
        }

        // authgroup defined
        foreach (\User::getUSER()->getAuthGroups() as $group) {
            foreach (self::findAll(["authgroupid" => $group->id]) as $system) {
                $systems[$system->solarSystemID] = $system;
            }
        }

        // trade hubs
        foreach (\eve\model\SolarSystem::getTradehubs() as $tradehub)
        {
            if (!isset($systems[$tradehub->id]))
            {
                $system = new \map\model\ClosestSystem();
                $system->solarSystemID = $tradehub->id;
                $system->showOnMap = true;
                $system->tradeHub = true;
                $systems[$tradehub->id] = $system;
            }
        }

        return $systems;
    }
}