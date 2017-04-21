<?php
namespace admin\model;

class SubscriptionTransaction extends \Model
{
    protected $_table = "vippy_subscriptions_journal";
    protected $_deletedField = "deleted";

    public $id;
    public $authgroupID;
    public $description;
    public $fromCharacterID;
    public $toCharacterID;
    public $amount;
    /** @dbField transactiondate */
    public $date;
    public $approved = false;
    /** @deleted */
    public $deleted = false;

    private $_authgroup = null;
    private $_fromCharacter = null;
    private $_toCharacter = null;


    function approve()
    {
        $this->approved = true;
        $this->store();

        $console = new \admin\console\Authgroup();
        $console->doBalance([$this->id]);
    }


    /**
     * Get authgroup
     * @return \admin\model\AuthGroup
     */
    function getAuthgroup()
    {
        if ($this->_authgroup === null && $this->authgroupID > 0)
            $this->_authgroup = new \admin\model\AuthGroup($this->authgroupID);

        return $this->_authgroup;
    }

    /**
     * Find authgroup
     * @return \admin\model\AuthGroup|null
     */
    function findAuthgroup()
    {
        \AppRoot::debug("findAuthGroup()");
        $paytocharacter = \eve\model\Character::findByID(\AppRoot::getDBConfig("wallet_api_charid"));
        $payUser = $paytocharacter->getUser();
        if (!$payUser)
            return null;

        $fromUser = ($this->getFromCharacter())?$this->getFromCharacter()->getUser():null;
        $toUser = ($this->getToCharacter())?$this->getToCharacter()->getUser():null;

        $character = null;
        if ($fromUser && $fromUser->id != $payUser->id)
            $character = $this->getFromCharacter();
        else if ($toUser && $toUser->id != $payUser->id)
            $character = $this->getToCharacter();

        if ($character != null && $character->getUser() != null) {
            if (count($character->getUser()->getAuthGroups()) == 1) {
                foreach ($character->getUser()->getAuthGroups() as $group) {
                    return $group;
                }
            }
        }

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
     * Check if we already have this transaction.
     * @return boolean
     */
    function exists()
    {
        $transaction = \admin\model\SubscriptionTransaction::findAll([
            "fromcharacterid" => $this->fromCharacterID,
            "tocharacterid"   => $this->toCharacterID,
            "amount"          => $this->amount,
            "transactiondate" => $this->date
        ]);
        if (count($transaction) > 0)
            return true;

        return false;
    }
}