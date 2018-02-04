<?php
namespace fleets\model;

class Member extends \Model
{
    protected $_table = "esi_fleet_member";
    protected $_keyfield = ["fleetID", "characterID"];

    public $fleetID;
    public $characterID;
    public $wingID;
    public $squadID;
    public $solarSystemID;
    public $shipTypeID;
    public $takeWarp;
}