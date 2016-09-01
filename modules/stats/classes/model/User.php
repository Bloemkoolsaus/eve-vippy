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

    function calcPoints()
    {
        $points = ($this->nrSigs/5);

        // logi/recon kills erbij optellen
        if ($this->reqSigs > 0)
            $points += (($this->nrKills-$this->reqSigs)*1.5);

        return round($points);
    }

    function calcRatio()
    {
        if ($this->calcPoints() > 0) {
            $this->ratio = 100;
            if ($this->nrKills > 0)
                $this->ratio = round(($this->calcPoints()/$this->nrKills)*100);
        } else
            $this->ratio = $this->nrKills*-1;

        return $this->ratio;
    }

    function calcScore()
    {
        $this->score = 0;
        if ($this->nrKills > 0)
            $this->score = round($this->calcRatio()/2);

        if ($this->score == 0)
            $this->score = 50 + $this->calcPoints();

        return $this->score;
    }

    function getScoreColor()
    {
        if ($this->score >= 500)
            return "00aa00";

        if ($this->score >= 200)
            return "11bb00";

        if ($this->score >= 80)
            return "33bb00";

        if ($this->score >= 60)
            return "66aa00";

        if ($this->score >= 50)
            return "88aa00";
        if ($this->score >= 40)
            return "ccaa00";

        if ($this->score >= 30)
            return "dd7700";
        if ($this->score >= 20)
            return "dd5500";
        if ($this->score > 0)
            return "ee2200";
        if ($this->score < 0)
            return "cc0000";

        return "777777";

    }

    function getScoreTitle()
    {
        if ($this->score >= 500)
            return "First Class";
        if ($this->score >= 200)
            return "Great";
        if ($this->score >= 80)
            return "Good";
        if ($this->score >= 60)
            return "Okay";
        if ($this->score >= 40)
            return "Mediocre";
        if ($this->score >= 30)
            return "Almost";
        if ($this->score != 0)
            return "Slacker";

        return "no-score";
    }
}