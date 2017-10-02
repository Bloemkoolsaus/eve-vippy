<?php
namespace admin\model;

class AccessList extends \Model
{
    protected $_table = "user_accesslist";

    public $id;
    public $ownerID;
    public $title;

    private $_users;
    private $_characters;
    private $_corporations;
    private $_alliances;
    private $_allowedUsers;

    function store()
    {
        parent::store();

        if ($this->_users !== null) {
            \MySQL::getDB()->delete("user_accesslist_user", ["accesslistid" => $this->id]);
            foreach ($this->getUsers() as $user) {
                \MySQL::getDB()->insert("user_accesslist_user", [
                    "accesslistid" => $this->id,
                    "userid" => $user["user"]->id,
                    "admin" => ($user["admin"])?1:0
                ]);
            }
        }

        if ($this->_corporations !== null) {
            \MySQL::getDB()->delete("user_accesslist_corporation", ["accesslistid" => $this->id]);
            foreach ($this->getCorporations() as $corp) {
                \MySQL::getDB()->insert("user_accesslist_corporation", [
                    "accesslistid" => $this->id,
                    "corporationid" => $corp->id
                ]);
            }
        }

        if ($this->_alliances !== null) {
            \MySQL::getDB()->delete("user_accesslist_alliance", ["accesslistid" => $this->id]);
            foreach ($this->getAlliances() as $alliance) {
                \MySQL::getDB()->insert("user_accesslist_alliance", [
                    "accesslistid" => $this->id,
                    "allianceid" => $alliance->id
                ]);
            }
        }

        if ($this->_characters !== null) {
            \MySQL::getDB()->delete("user_accesslist_characters", ["accesslistid" => $this->id]);
            foreach ($this->getCharacters() as $character) {
                \MySQL::getDB()->insert("user_accesslist_characters", [
                    "accesslistid" => $this->id,
                    "characterid" => $character->id
                ]);
            }
        }
    }


    /**
     * Can user admin?
     * @param $userID
     * @return bool
     */
    function canAdmin($userID)
    {
        if ($this->ownerID == $userID)
            return true;

        foreach ($this->getUsers() as $user) {
            if ($user["user"]->id == $userID) {
                if ($user["admin"])
                    return true;
            }
        }

        return false;
    }


    /**
     * Get allowed users
     * @return \users\model\User[]
     */
    function getAllowedUsers()
    {
        if ($this->_allowedUsers === null)
        {
            $this->_users = [];
            if ($results = \MySQL::getDB()->getRows("select u.*
                                                     from   users u
                                                        inner join user_accesslist_user a on a.userid = u.id
                                                     where  a.accesslistid = ?
                                                  union
                                                    select  u.*
                                                    from    users u
                                                        inner join characters c on c.userid = u.id
                                                        inner join user_accesslist_corporation ac on ac.corporationid = c.corpid
                                                    where   ac.accesslistid = ?
                                                  union
                                                    select  u.*
                                                    from    users u
                                                        inner join characters c on c.userid = u.id
                                                        inner join corporations cc on cc.id = c.corpid
                                                        inner join user_accesslist_alliance aa on aa.allianceid = cc.allianceid
                                                    where   aa.accesslistid = ?
                                                group by id
                                                order by displayname"
                                    , [$this->id, $this->id, $this->id]))
            {
                foreach ($results as $result) {
                    $user = new \users\model\User();
                    $user->load($result);
                    $this->_allowedUsers[] = $user;
                }
            }
        }
        return $this->_allowedUsers;
    }

    /**
     * Get users
     * @return \users\model\User[]
     */
    function getUsers()
    {
        if ($this->_users === null)
        {
            $this->_users = [];
            if ($results = \MySQL::getDB()->getRows("select u.*, a.admin
                                                     from   users u
                                                        inner join user_accesslist_user a on a.userid = u.id
                                                     where  a.accesslistid = ?"
                                                , [$this->id]))
            {
                foreach ($results as $result) {
                    $user = new \users\model\User();
                    $user->load($result);
                    $this->addUser($user, ($result["admin"])?true:false);
                }
            }
        }
        return $this->_users;
    }

    /**
     * add user
     * @param \users\model\User $user
     * @param bool $asAdmin
     */
    function addUser(\users\model\User $user, $asAdmin=false)
    {
        if ($this->_users === null)
            $this->getUsers();

        $this->_users[] = ["user" => $user, "admin" => $asAdmin];
    }

    /**
     * Get characters
     * @return \eve\model\Corporation[]
     */
    function getCharacters()
    {
        if ($this->_characters === null)
        {
            $this->_characters = [];
            if ($results = \MySQL::getDB()->getRows("select c.*
                                                     from   characters c
                                                        inner join user_accesslist_characters a on a.characterid = c.id
                                                     where  a.accesslistid = ?"
                                            , [$this->id]))
            {
                foreach ($results as $result) {
                    $char = new \eve\model\Character();
                    $char->load($result);
                    $this->addCharacter($char);
                }
            }
        }
        return $this->_characters;
    }

    /**
     * add corporation
     * @param \eve\model\Character $character
     */
    function addCharacter(\eve\model\Character $character)
    {
        if ($this->_characters === null)
            $this->getCharacters();

        $this->_characters[] = $character;
    }

    /**
     * Remove alliance
     * @param $characterID
     */
    function removeCharacter($characterID)
    {
        foreach ($this->getCharacters() as $key => $character) {
            if ($character->id == $characterID)
                unset($this->_characters[$key]);
        }
    }

    /**
     * Get corporations
     * @return \eve\model\Corporation[]
     */
    function getCorporations()
    {
        if ($this->_corporations === null)
        {
            $this->_corporations = [];
            if ($results = \MySQL::getDB()->getRows("select c.*
                                                     from   corporations c
                                                        inner join user_accesslist_corporation a on a.corporationid = c.id
                                                     where  a.accesslistid = ?"
                , [$this->id]))
            {
                foreach ($results as $result) {
                    $corp = new \eve\model\Corporation();
                    $corp->load($result);
                    $this->addCorporation($corp);
                }
            }
        }
        return $this->_corporations;
    }

    /**
     * add corporation
     * @param \eve\model\Corporation $corporation
     */
    function addCorporation(\eve\model\Corporation $corporation)
    {
        if ($this->_corporations === null)
            $this->getCorporations();

        $this->_corporations[] = $corporation;
    }

    /**
     * Remove alliance
     * @param $corporationID
     */
    function removeCorporation($corporationID)
    {
        foreach ($this->getCorporations() as $key => $corporation) {
            if ($corporation->id == $corporationID)
                unset($this->_corporations[$key]);
        }
    }

    /**
     * Get corporations
     * @return \eve\model\Corporation[]
     */
    function getAlliances()
    {
        if ($this->_alliances === null)
        {
            $this->_alliances = [];
            if ($results = \MySQL::getDB()->getRows("select c.*
                                                     from   alliances c
                                                        inner join user_accesslist_alliance a on a.allianceid = c.id
                                                     where  a.accesslistid = ?"
                                            , [$this->id]))
            {
                foreach ($results as $result) {
                    $alliance = new \eve\model\Alliance();
                    $alliance->load($result);
                    $this->_alliances[] = $alliance;
                }
            }
        }
        return $this->_alliances;
    }

    /**
     * add alliance
     * @param \eve\model\Alliance $alliance
     */
    function addAlliance(\eve\model\Alliance $alliance)
    {
        if ($this->_alliances === null)
            $this->getAlliances();

        $this->_alliances[] = $alliance;
    }

    /**
     * Remove alliance
     * @param $allianceID
     */
    function removeAlliance($allianceID)
    {
        foreach ($this->getAlliances() as $key => $alliance) {
            if ($alliance->id == $allianceID)
                unset($this->_alliances[$key]);
        }
    }




    /**
     * Find accesslists by character
     * @param \eve\model\Character $character
     * @return \admin\model\AccessList[]
     */
    public static function findByCharacter(\eve\model\Character $character)
    {
        $from = ["user_accesslist a"];
        $where = [];

        $from[] = "user_accesslist_characters c on c.accesslistid = a.id";
        $where[] = "c.characterid = ".$character->id;

        $joins[] = "user_accesslist_corporation cc on cc.accesslistid = a.id";
        $where[] = "cc.corporationid = ".$character->corporationID;

        if ($character->getCorporation() && $character->getCorporation()->allianceID) {
            $joins[] = "user_accesslist_alliance aa on aa.accesslistid = a.id";
            $where[] = "aa.allianceid = ".$character->getCorporation()->allianceID;
        }

        $accessLists = [];
        if ($results = \MySQL::getDB()->getRows("select a.*
                                                 from   ".implode(" left join", $from)." 
                                                 where  ".implode(" or", $where)."
                                                 group by a.id"))
        {
            foreach ($results as $result) {
                $list = new \admin\model\AccessList();
                $list->load($result);
                $accessLists[] = $list;
            }
        }

        return $accessLists;
    }
}