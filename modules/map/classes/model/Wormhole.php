<?php
namespace map\model;

class Wormhole extends \scanning\model\Wormhole
{
    private $_solarsystem;

    /**
     * Get solarsystem
     * @return \map\model\System|null
     */
    function getSolarsystem()
    {
        if ($this->_solarsystem == null && $this->solarSystemID > 0)
            $this->_solarsystem = new \map\model\System($this->solarSystemID);

        return $this->_solarsystem;
    }
}