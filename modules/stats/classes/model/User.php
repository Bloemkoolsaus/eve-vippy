<?php
namespace stats\model;

class User extends \Model
{
    protected $_table = "stats_users";
    protected $_keyfield = array("userid", "authgroupid", "year", "month");

    public $userID;
    public $corporationID;
    public $authgroupID;
    public $year;
    public $month;
    public $nrSigs = 0;
    public $nrWormholes = 0;
    public $nrKills = 0;
    public $reqSigs = 0;
    public $hoursOnline = 0;
    public $ratio = 0;
    public $score = 0;
    public $updatedate;

    private $_user;
    private $_corporation;

    function store()
    {
        $this->updatedate = date("Y-m-d H:i:s");
        $this->score = $this->calcScore();
        parent::store();
    }

    /**
     * Get user
     * @return \users\model\User
     */
    function getUser()
    {
        if ($this->_user === null)
            $this->_user = new \users\model\User($this->userID);

        return $this->_user;
    }

    /**
     * Get corporation
     * @return \eve\model\Corporation
     */
    function getCorporation()
    {
        if ($this->_corporation === null)
            $this->_corporation = new \eve\model\Corporation($this->corporationID);

        return $this->_corporation;
    }

    function calcRatio()
    {
        if ($this->reqSigs > 0)
            $this->ratio = round(($this->nrSigs / $this->reqSigs) * 100);
        else
            $this->ratio = 100;

        return $this->ratio;
    }

    function calcScore()
    {
        $this->score = log($this->calcRatio()/100,30)*100;
        return $this->score;
    }

    function getScoreColor()
    {
        if ($this->score > 100)
            return "00aa00";
        if ($this->score > 80)
            return "11bb00";
        if ($this->score > 70)
            return "33bb00";
        if ($this->score > 50)
            return "66aa00";
        if ($this->score > 40)
            return "88aa00";
        if ($this->score > 30)
            return "dd7700";
        if ($this->score > 20)
            return "dd5500";

        return "cc0000";
    }

    function getScoreTitle()
    {
        if ($this->score >= 110)     // meer dan 5x wat je zou moeten doen!!
            return "First Class";
        if ($this->score > 80)
            return "Great";
        if ($this->score > 70)
            return "Good";
        if ($this->score > 50)
            return "Okay";
        if ($this->score > 40)
            return "Mediocre";
        if ($this->score > 20)
            return "Slacker";
        if ($this->score > 0)
            return "Slacker";

        return "no score";
    }
}