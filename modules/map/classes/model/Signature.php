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


    function store(\map\model\Map $map=null)
    {
        if ($map)
            $this->authGroupID = $map->authgroupID;

        if ($this->scannedBy == 0) {
            $this->scannedBy = \User::getUSER()->id;
            $this->scanDate = date("Y-m-d H:i:s");
        }

        $this->updateBy = \User::getUSER()->id;
        $this->updateDate = date("Y-m-d H:i:s");

        // Tellen in statistieken?
        $countInStats = false;
        if ($this->id) {
            // Gewijzigd?
            $old = new \map\model\Signature($this->id);
            if (trim(strtoupper($this->sigType)) != trim(strtoupper($old->sigType))) {
                if (strlen(trim($this->sigType)) > 0)
                    $countInStats = true;
            }
        } else {
            // Nieuwe signature
            if (strlen(trim($this->sigType)) > 0)
                $countInStats = true;
        }

        parent::store();


        // Toevoegen in statistsieken
        if ($countInStats) {
            $stat = new \stats\model\Signature();
            $stat->userID = \User::getUSER()->id;
            $stat->corporationID = \User::getUSER()->getMainCharacter()->getCorporation()->id;
            $stat->signatureID = $this->id;
            if ($map)
                $stat->chainID = $map->id;
            $stat->scandate = date("Y-m-d H:i:s");
            $stat->store();
        }

        // Systeem toevoegen?
        if ($map && $map->getSetting("create-unmapped")) {
            if (strtolower(trim($this->sigType)) == "wh" && !$this->deleted) {
                $controller = new \map\controller\Map();
                $controller->addWormholeToMap($map, $this);
            }
        }

        // Check wh-nummber. Connection bijwerken.
        if ($this->typeID > 0 && $this->typeID != 9999) {
            // Parse signature name om de de juiste connectie te zoeken.
            $parts = explode(" ", $this->sigInfo);
            $parts = explode("-", $parts[0]);
            $wormholename = (count($parts) > 1) ? $parts[1] : $parts[0];
            \AppRoot::debug("UPDATE Connection Type: ".$wormholename);

            // Zoek dit wormhole
            foreach (\map\model\Wormhole::getWormholesByAuthgroup($this->authGroupID) as $wormhole) {
                if (trim(strtolower($wormhole->name)) == trim(strtolower($wormholename))) {
                    $fromWormhole = \map\model\Wormhole::getWormholeBySystemID($this->solarSystemID, $map->id);
                    $connection = \map\model\Connection::getConnectionByWormhole($fromWormhole->id, $wormhole->id, $map->id);
                    if ($connection != null) {
                        if ($connection->fromWormholeID == $wormhole->id) {
                            $connection->fromWHTypeID = 9999;
                            $connection->toWHTypeID = $this->typeID;
                        } else {
                            $connection->toWHTypeID = 9999;
                            $connection->fromWHTypeID = $this->typeID;
                        }
                        $connection->store(false);
                    }
                }
            }
        }

        // Check open sigs.
        $controller = new \scanning\controller\Signature();
        $controller->checkOpenSignatures($this->getSolarSystem(), $map);
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