<?php
namespace notices\model;

class Drifter extends \Model
{
    protected $_keyfield = ["solarSystemID", "authGroupID"];

    public $solarSystemID;
    public $authGroupID;
    public $nrDrifters;
    public $comments;
    public $updateBy;
    public $updateDate;

    function store()
    {
        $this->updateBy = (\User::getUSER())?\User::getUSER()->id:null;
        $this->updateDate = date("Y-m-d H:i:s");
        parent::store();
        \Cache::memory()->remove(["map", $this->authGroupID, "drifters"]);
    }

    function delete()
    {
        parent::delete();
        \Cache::memory()->remove(["map", $this->authGroupID, "drifters"]);
    }
}