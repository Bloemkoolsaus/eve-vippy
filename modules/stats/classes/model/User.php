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
        $this->ratio = 0;
        if ($this->reqSigs > 0)
            $this->ratio = round(($this->nrSigs / $this->reqSigs) * 100);

        return $this->ratio;
    }

    function calcScore()
    {
        if ($this->nrSigs == 0 && $this->nrKills == 0)
            $this->score = 0;
        else {
            if ($this->reqSigs == $this->nrSigs)
                $this->score = ($this->reqSigs == 0) ? 50 : 10;
            else if ($this->ratio == 0) {
                if ($this->reqSigs > 0)
                    $this->score = $this->reqSigs * -1;
                else {
                    $this->score = 50 + $this->nrSigs;
                    if ($this->score >= 100)
                        $this->score = 99;
                }
            } else
                $this->score = log($this->calcRatio() / 100, 30) * 100;
        }

        return $this->score;
    }

    function getScoreColor()
    {
        if ($this->score > 100)
            return "00aa00";
        if ($this->score > 80)
            return "11bb00";
        if ($this->score > 65)
            return "33bb00";
        if ($this->score > 50)
            return "66aa00";
        if ($this->score > 40)
            return "88aa00";
        if ($this->score > 30)
            return "dd7700";
        if ($this->score > 20)
            return "dd5500";
        if ($this->score > 0)
            return "ee2200";
        if ($this->score < 0)
            return "cc0000";

        return "777777";

    }

    function getScoreTitle()
    {
        if ($this->score >= 110)
            return "First Class";
        if ($this->score >= 80)
            return "Great";
        if ($this->score >= 65)
            return "Good";
        if ($this->score >= 50)
            return "Okay";
        if ($this->score >= 40)
            return "Mediocre";
        if ($this->score != 0)
            return "Slacker";

        return "no-score";
    }
}