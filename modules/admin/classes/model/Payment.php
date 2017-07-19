<?php
namespace admin\model;

class Payment extends \Model
{
    protected $_table = "vippy_subscriptions_journal";
    protected $_deletedField = "deleted";

    public $id;
    public $authgroupID;
    public $description;
    public $fromCharacterID;
    public $fromCorporationID;
    public $toCharacterID;
    public $amount;
    /** @dbField transactiondate */
    public $date;
    public $approved = false;
    /** @deleted */
    public $deleted = false;

    private $_authgroup = null;
    private $_fromCharacter = null;
    private $_fromCorporation = null;
    private $_fromUser = null;
    private $_toCharacter = null;
    private $_toUser = null;


    /**
     * Get authgroup
     * @return \admin\model\AuthGroup
     */
    function getAuthgroup()
    {
        if ($this->_authgroup === null) {
            if (!$this->authgroupID) {
                $group = $this->findAuthgroup();
                if ($group) {
                    $this->_authgroup = $group;
                    $this->authgroupID = $group->id;
                }
            } else
                $this->_authgroup = new \admin\model\AuthGroup($this->authgroupID);
        }

        return $this->_authgroup;
    }

    /**
     * Find authgroup
     * @return \admin\model\AuthGroup|null
     */
    function findAuthgroup()
    {
        \AppRoot::debug("findAuthGroup()");
        $paytocharacter = \eve\model\Character::findByID(\admin\model\Payment::getWalletCharacterID());
        $payUser = $paytocharacter->getUser();
        if (!$payUser)
            return null;

        if ($this->getFromUser() && $this->getFromUser()->id != $payUser->id) {
            $groups = $this->getFromUser()->getAuthGroups();
            if (count($groups) > 0)
                return array_shift($groups);
        }

        if ($this->getToUser() && $this->getToUser()->id != $payUser->id) {
            $groups = $this->getToUser()->getAuthGroups();
            if (count($groups) > 0)
                return array_shift($groups);
        }

        // Payment komt van een eigen toon af
        $groups = $payUser->getAuthGroups();
        if (count($groups) > 0)
            return array_shift($groups);

        return null;
    }

    /**
     * Get from character
     * @return \eve\model\Character
     */
    function getFromCharacter()
    {
        if ($this->_fromCharacter === null)
            $this->_fromCharacter = \eve\model\Character::findByID($this->fromCharacterID);

        return $this->_fromCharacter;
    }

    /**
     * Get from user
     * @return \users\model\User|null
     */
    function getFromUser()
    {
        if ($this->_fromUser === null) {
            if ($this->getFromCharacter())
                $this->_fromUser = $this->getFromCharacter()->getUser();
        }
        return $this->_fromUser;
    }

    /**
     * Get from corporation
     * @return \eve\model\Corporation|null
     */
    function getFromCorporation()
    {
        if ($this->_fromCorporation === null) {
            if ($this->fromCorporationID)
                $this->_fromCorporation = new \eve\model\Corporation($this->fromCorporationID);
            else {
                if ($this->getFromCharacter())
                    $this->_fromCorporation = $this->getFromCharacter()->getCorporation();
            }
        }

        return $this->_fromCorporation;
    }

    /**
     * Get to character
     * @return \eve\model\Character
     */
    function getToCharacter()
    {
        if ($this->_toCharacter === null)
            $this->_toCharacter = \eve\model\Character::findByID($this->toCharacterID);

        return $this->_toCharacter;
    }

    /**
     * Get to user
     * @return \users\model\User|null
     */
    function getToUser()
    {
        if ($this->_toUser === null) {
            if ($this->getToCharacter())
                $this->_toUser = $this->getToCharacter()->getUser();
        }
        return $this->_toUser;
    }

    /**
     * Check if we already have this transaction.
     * @return boolean
     */
    function exists()
    {
        $transaction = \admin\model\Payment::findAll([
            "fromcharacterid" => $this->fromCharacterID,
            "tocharacterid"   => $this->toCharacterID,
            "amount"          => $this->amount,
            "transactiondate" => $this->date
        ]);
        if (count($transaction) > 0)
            return true;

        return false;
    }


    public static function getWalletApiKey()
    {
        return [
            "keyid" => \Config::getCONFIG()->get("system_payments_api_keyid"),
            "vcode" => \Config::getCONFIG()->get("system_payments_api_vcode")
        ];
    }

    public static function getWalletCharacterID()
    {
        return \Config::getCONFIG()->get("system_payments_characterid");
    }
}