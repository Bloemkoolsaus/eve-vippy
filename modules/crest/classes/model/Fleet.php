<?php
namespace crest\model;

class Fleet extends \Model
{
    public $id;
    public $bossID;
    public $authGroupID;
    public $active = false;
    public $lastUpdate;

    private $_boss;
    private $_authgroup;


    /**
     * Get boss
     * @return \crest\model\Character
     */
    function getBoss()
    {
        if ($this->_boss === null)
            $this->_boss = new \crest\model\Character($this->bossID);

        return $this->_boss;
    }

    /**
     * Get authorization group
     * @return \admin\model\AuthGroup
     */
    function getAuthGroup()
    {
        if ($this->_authgroup === null)
            $this->_authgroup = new \admin\model\AuthGroup($this->authGroupID);

        return $this->_authgroup;
    }
}