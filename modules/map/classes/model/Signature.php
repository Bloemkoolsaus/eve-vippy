<?php
namespace map\model;

class Signature extends \Model
{
    protected $_table = "mapsignatures";

    public $id = 0;
    public $solarSystemID = 0;
    public $authGroupID = null;
    public $sigID = null;
    public $sigType;
    public $typeID = 0;
    public $sigInfo;
    public $signalStrength;
    public $scanDate = null;
    public $updateDate;
    public $scannedBy = 0;
    public $updateBy = 0;
    public $deleted = false;

    private $_solarsystem;
    private $_wormholeType;
    private $_scannedUser;
    private $_updatedUser;


    /**
     * -- DO NOT CALL DIRECTLY --
     * Use \map\controller\Signature()->storeSignature() instead!!!
     */
    function store()
    {
        if ($this->scannedBy == 0) {
            $this->scannedBy = \User::getUSER()->id;
            $this->scanDate = date("Y-m-d H:i:s");
        }

        $this->updateBy = \User::getUSER()->id;
        $this->updateDate = date("Y-m-d H:i:s");
        $this->sigType = strtolower($this->sigType);

        parent::store();
    }

    function delete()
    {
        $this->deleted = true;
        $this->store();
    }

    /**
     * Get solar system
     * @return SolarSystem
     */
    function getSolarSystem()
    {
        if ($this->_solarsystem === null)
            $this->_solarsystem = new \map\model\SolarSystem($this->solarSystemID);

        return $this->_solarsystem;
    }

    /**
     * Is wormhole?
     * @return bool
     */
    function isWormhole()
    {
        if ($this->sigType == "wh")
            return true;

        return false;
    }

    /**
     * Get wormhole type
     * @return WormholeType|null
     */
    function getWormholeType()
    {
        if ($this->_wormholeType === null) {
            if ($this->isWormhole())
                $this->_wormholeType = new \map\model\WormholeType($this->typeID);
        }

        return $this->_wormholeType;
    }

    /**
     * Get user die deze signature als eerste getscant heeft
     * @return \users\model\User
     */
    function getScannedByUser()
    {
        if ($this->_scannedUser === null)
            $this->_scannedUser = new \users\model\User($this->scannedBy);

        return $this->_scannedUser;
    }

    /**
     * Get user die deze signature als laatste heeft bijgewerkt
     * @return \users\model\User
     */
    function getUpdatedByUser()
    {
        if ($this->_updatedUser === null)
            $this->_updatedUser = new \users\model\User($this->scannedBy);

        return $this->_updatedUser;
    }


    /**
     * find all signatures
     * @param array $conditions
     * @param array $orderby
     * @param string|null $class
     * @return \map\model\Signature[]
     */
    public static function findAll($conditions=[], $orderby=["sigid"], $class=null)
    {
        return parent::findAll($conditions, $orderby, $class);
    }
}