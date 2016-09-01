<?php
namespace stats\model;

class Kills extends \Model
{
    protected $_keyfield = array("userid", "killdate", "shiptypeid");

    public $userID;
    public $killdate;
    public $shipTypeID;
    public $nrKills;
}