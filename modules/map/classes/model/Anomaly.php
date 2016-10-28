<?php
namespace map\model;

class Anomaly extends \Model
{
    public $id;
    public $typeID;
    public $authGroupID;
    public $solarSystemID;
    public $signatureID;
    public $description;

    private $_type;


    /**
     * Get type
     * @return \map\model\AnomalyType|null
     */
    function getType()
    {
        if ($this->_type === null)
            $this->_type = \map\model\AnomalyType::findById($this->typeID);

        return $this->_type;
    }
}