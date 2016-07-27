<?php
namespace stats\model;

class Kills extends \Model
{
    public $id = 0;
    public $userID;
    public $nrKills;
    public $requiredSigs;
    public $killdate;
}