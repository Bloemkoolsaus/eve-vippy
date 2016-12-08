<?php
namespace crest\model;

class Token extends \Model
{
    protected $_keyfield = ["tokentype","tokenid"];

    public $tokentype;
    public $tokenid;
    public $ownerHash;
    public $state;
    public $accessToken;
    public $refreshToken;
    public $expires;
    public $scopes;
    public $updateDate;

    function store()
    {
        $this->updateDate = date("Y-m-d H:i:s");
        parent::store();
    }


    /**
     * Still valid? Can we still use this token?
     * @param bool $refreshWhenExpired
     * @return bool
     */
    function isValid($refreshWhenExpired=false)
    {
        if (!$this->refreshToken)
            return false;

        if ($this->isExpired()) {
            if ($refreshWhenExpired) {
                $this->refresh();
                return $this->isValid(false);
            }
            return false;
        }

        return true;
    }

    /**
     * Has this token expired?
     * @return bool
     */
    function isExpired()
    {
        if ($this->expires) {
            if (strtotime("now") >= strtotime($this->expires))
                return true;
        }
        return false;
    }

    function refresh()
    {
        $oauth = new \crest\Login();
        if ($oauth->refresh($this)) {
            $this->load();
            return true;
        }

        return false;
    }
}