<?php
namespace eve\model;

class Alliance extends \Model
{
    protected $_table = "alliances";

    public $id = 0;
    public $name;
    public $ticker;

    private $_corporations = null;


    /**
     * Get corporations
     * @return \eve\model\Corporation[]
     */
    function getCorporations()
    {
        if ($this->_corporations == null)
            $this->_corporations = \eve\model\Corporation::getCorporationsByAlliance($this->id);

        return $this->_corporations;
    }
}