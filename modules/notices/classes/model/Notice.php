<?php
namespace notices\model;

class Notice extends \Model
{
    protected $_table = "notices";

    public $id = 0;
    public $title;
    public $body;
    public $typeID = 0;
    public $persistant = false;
    public $global = false;
    public $solarSystemID = 0;
    public $userID = 0;
    public $authGroupID = 0;
    public $messageDate = null;
    public $expireDate = null;
    public $deleted = false;

    private $_system = null;
    private $_user = null;

    function store()
    {
        if (!$this->userID)
            $this->userID = (\User::getUSER()) ? \User::getUSER()->id : 0;

        if (!$this->authGroupID)
            $this->authGroupID = \User::getUSER()->getCurrentAuthGroupID();

        if (!$this->messageDate)
            $this->messageDate = date("Y-m-d H:i:s");

        if (!$this->expireDate)
            $this->expireDate = date("Y-m-d", mktime(0,0,0,date("m")+2,0,date("Y")));

        parent::store();
        \Cache::memory(0)->set(["notifications", "update"], strtotime("now"));
    }

    function getTitle()
    {
        $title = "";
        if ($this->solarSystemID > 0)
            $title = $this->getSystem()->name.": ".

        $title .= $this->title;
        return $title;
    }

    function getTypeName()
    {
        if ($result = \MySQL::getDB()->getRow("SELECT * FROM notice_types WHERE id = ?", array($this->typeID)))
            return $result["name"];
        else
            return "";
    }

    function isExpired()
    {
        if ($this->expireDate) {
            if (strtotime($this->expireDate) < strtotime("now"))
                return true;
        }

        return false;
    }

    function markRead($userID=false)
    {
        if (!$userID)
            $userID = (\User::getUSER()) ? \User::getUSER()->id : false;

        if ($userID)
        {
            $data = array("noticeid" => $this->id, "userid" => $userID);
            \MySQL::getDB()->updateinsert("notices_read", $data, $data);
            return true;
        }
        else
            return false;
    }


    /**
     * Get system
     * @return \eve\model\SolarSystem
     */
    function getSystem()
    {
        if ($this->_system == null)
            $this->_system = new \eve\model\SolarSystem($this->solarSystemID);

        return $this->_system;
    }

    /**
     * Get user
     * @return \users\model\User|null
     */
    function getUser()
    {
        if ($this->_user === null)
            $this->_user = new \users\model\User($this->userID);

        return $this->_user;
    }
}