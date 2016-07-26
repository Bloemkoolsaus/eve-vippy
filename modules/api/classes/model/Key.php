<?php
namespace api\model;

class Key extends \Model
{
    protected $_table = "vippy_api_key";

    public $id = 0;
    public $authGroupID;
    public $description;
    public $apiKey;
    public $deleted = false;

    private $_authgroup;
    private $_ipAddresses;

    /**
     * Get authgroup
     * @return \admin\model\AuthGroup
     */
    function getAuthgroup()
    {
        if ($this->_authgroup === null)
            $this->_authgroup = new \admin\model\AuthGroup($this->authGroupID);

        return $this->_authgroup;
    }

    /**
     * Get ip addresses
     * @return \api\model\Ip[]
     */
    function getIPAddresses()
    {
        if ($this->_ipAddresses === null)
            $this->_ipAddresses = \api\model\Ip::findAll(["apikeyid" => $this->id]);

        return $this->_ipAddresses;
    }
}