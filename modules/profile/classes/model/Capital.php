<?php
namespace profile\model;

class Capital extends \Model
{
    protected $_table = "profile_capitals";

    public $id = 0;
    public $userID;
    public $shipID;
    public $solarSystemID;
    public $description;

    private $_ship = null;
    private $_user = null;
    private $_system = null;


    /**
     * Get number of jumps to a certain system
     * @param int $solarSystemID
     * @return number
     */
    function getNumberOfJumpsToSystem($solarSystemID)
    {
        $nrJumps = $this->getSolarsystem()->getNrCapitalJumps($solarSystemID, $this->getMaxJumprange());
        \AppRoot::debug("getNumberOfJumpsToSystem($solarSystemID): ".$nrJumps);
        return $nrJumps;
    }

    /**
     * Get max jumprange
     * @param string $jumpDriveCalibrationLevel
     * @return number
     */
    function getMaxJumprange($jumpDriveCalibrationLevel=null)
    {
        if (!$jumpDriveCalibrationLevel)
            $jumpDriveCalibrationLevel = $this->getUser()->getSetting("jumpdrivecal");

        if (!$jumpDriveCalibrationLevel)
            $jumpDriveCalibrationLevel = 4;

        return $this->getShip()->getMaxJumprange($jumpDriveCalibrationLevel);
    }

    /**
     * Get ship
     * @return \eve\model\Ship
     */
    function getShip()
    {
        if ($this->_ship == null)
            $this->_ship = new \eve\model\Ship($this->shipID);

        return $this->_ship;
    }

    /**
     * Get user
     * @return \users\model\User
     */
    function getUser()
    {
        if ($this->_user == null)
            $this->_user = new \users\model\User($this->userID);

        return $this->_user;
    }

    /**
     * Get solarsystem
     * @return \eve\model\SolarSystem
     */
    function getSolarsystem()
    {
        if ($this->_system == null)
            $this->_system = new \eve\model\SolarSystem($this->solarSystemID);

        return $this->_system;
    }
}