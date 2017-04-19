<?php
namespace admin\model;

class SubscriptionTransaction extends \Model
{
    protected $_table = "vippy_subscriptions_journal";

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
        $paytocharacter = new \eve\model\Character(\AppRoot::getDBConfig("wallet_api_charid"));

        $character = null;
        if ($this->getFromCharacter()->getUser() != $paytocharacter->getUser())
            $character = $this->getFromCharacter();
        else if ($this->getToCharacter()->getUser() != $paytocharacter->getUser())
            $character = $this->getToCharacter();

        if ($character != null && $character->getUser() != null) {
            \AppRoot::debug($character->name);
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
            $this->_fromCharacter = new \eve\model\Character($this->fromCharacterID);

        return $this->_fromCharacter;
    }

    /**
     * Get to character
     * @return \eve\model\Character
     */
    function getToCharacter()
    {
        if ($this->_toCharacter === null)
            $this->_toCharacter = new \eve\model\Character($this->toCharacterID);

        return $this->_toCharacter;
    }

    /**
     * Check if we already have this transaction.
     * @return boolean
     */
    function exists()
    {
        if ($results = \MySQL::getDB()->getRows("SELECT	*
                                                FROM	vippy_subscriptions_journal
                                                WHERE	fromcharacterid = ?
                                                AND		tocharacterid = ?
                                                AND		amount = ?
                                                AND		transactiondate = ?"
                            , array($this->fromCharacterID, $this->toCharacterID, $this->amount, $this->date)))
        {
            return true;
        }

        return false;
    }
}