<?php
namespace admin\model;

class Journal extends \Model
{
    protected $_table = "user_auth_groups_journal";

    public $id;
    public $authgroupID;
    public $what;
    public $whatID;
    public $amount;
    public $balance;
    public $description;
    /** @dbField transactiondate */
    public $date;

    private $_authgroup = null;
    private $_whatObject = null;


    function store()
    {
        if (!$this->date)
            $this->date = date("Y-m-d H:i:s");

        // Check if new
        $this->balance = $this->getAuthgroup()->balance;
        parent::store();

        $this->getAuthgroup()->balance += $this->amount;
        $this->getAuthgroup()->store();
    }

    function getType()
    {
        return ucfirst($this->what);
    }

    function getBalance()
    {
        return $this->balance + $this->amount;
    }

    function getWhatObject()
    {
        if ($this->_whatObject === null) {
            if ($this->what == "subscription")
                $this->_whatObject = new \admin\model\Subscription($this->whatID);
            if ($this->what == "payment")
                $this->_whatObject = new \admin\model\Payment($this->whatID);
        }
        return $this->_whatObject;
    }



    /**
     * Get authgroup
     * @return \admin\model\AuthGroup|null
     */
    function getAuthgroup()
    {
        if ($this->_authgroup === null && $this->authgroupID)
            $this->_authgroup = new \admin\model\AuthGroup($this->authgroupID);

        return $this->_authgroup;
    }
}