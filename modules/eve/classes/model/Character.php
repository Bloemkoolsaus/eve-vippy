<?php
namespace eve\model;

class Character
{
    public $id;
    public $userID;
    public $name;
    public $corporationID;
    public $isDirector = false;
    public $isCEO = false;
    public $titles = array();
    public $updatedate;
    public $isAuthorized = null;
    public $authMessage = null;

    private $_user;
    private $_corporation;
    private $_authgroups;
    private $_accesslists;
    private $_token;

    function __construct($id=false)
    {
        if ($id) {
            $this->id = $id;
            $this->load();
        }
    }

    function load($result=false)
    {
        if (!$result)
        {
            $cache = \Cache::file()->get("characters/" . $this->id);
            if ($cache) {
                $result = json_decode($cache, true);
            } else {
                $result = \MySQL::getDB()->getRow("SELECT * FROM characters WHERE id = ?", [$this->id]);
                \Cache::file()->set("characters/" . $this->id, $result);
            }
        }

        if ($result)
        {
            $this->id = $result["id"];
            $this->userID = $result["userid"];
            $this->name = $result["name"];
            $this->corporationID = $result["corpid"];
            $this->isDirector = ($result["isdirector"]>0)?true:false;
            $this->isCEO = ($result["isceo"]>0)?true:false;
            $this->updatedate = $result["updatedate"];
            $this->isAuthorized = ($result["authstatus"]>0)?true:false;
            $this->authMessage = $result["authmessage"];
        }
    }

    function store()
    {
        if ($this->id == 0)
            return false;
        if (!$this->name || strlen(trim($this->name)) == 0)
            return false;

        if ($this->getCorporation()) {
            if ($this->getCorporation()->ceoID == $this->id)
                $this->isCEO = true;
        }

        $data = [
            "id"			=> $this->id,
            "name"			=> $this->name,
            "userid"		=> $this->userID,
            "corpid"		=> $this->corporationID,
            "isdirector"	=> ($this->isDirector())?1:0,
            "isceo"			=> ($this->isCEO())?1:0,
            "authstatus"    => ($this->isAuthorized)?1:0,
            "authmessage"   => $this->authMessage,
            "updatedate"	=> date("Y-m-d H:i:s")
        ];
        \MySQL::getDB()->updateinsert("characters", $data, ["id" => $this->id]);

        \Cache::file()->remove("characters/" . $this->id);
        if ($this->getUser() != null)
            $this->getUser()->resetCache();

        return true;
    }

    function isCEO()
    {
        return $this->isCEO;
    }

    function isDirector()
    {
        if ($this->isCEO())
            return true;

        return $this->isDirector;
    }

    function getTitle()
    {
        if ($this->isCEO())
            return "CEO";

        if ($this->isDirector())
            return "Director";

        return "";
    }

    function getAuthStatus($reset=false)
    {
        if ($reset) {
            $this->isAuthorized = null;
            $this->authMessage = null;
        }

        \AppRoot::debug("getAuthStatus(".$this->name.",".$reset.")");
        if ($this->isAuthorized === null)
        {
            $this->isAuthorized = false;

            // Heeft een geldige SSO token
            /** @var \sso\model\Token $token */
            $token = \sso\model\Token::findAll(["tokentype" => "character", "tokenid" => $this->id]);
            if ($token) {
                \AppRoot::debug("We have an active SSO token for ".$this->name);
                foreach ($this->getAuthGroups() as $group) {
                    \AppRoot::debug($group->name);
                    $this->isAuthorized = true;
                    break;
                }
                if (!$this->isAuthorized)
                    $this->authMessage = "Not a member of an access group";
            } else
                $this->authMessage = "No valid SSO authentication token";

            $this->store();
        }

        return $this->authMessage;
    }

    /**
     * Get authgroups
     * @param bool $allowedOnly
     * @return \admin\model\AuthGroup[]
     */
    function getAuthGroups($allowedOnly=true)
    {
        if ($this->_authgroups === null)
            $this->_authgroups = \admin\model\AuthGroup::getAuthgroupsByCorporation($this->corporationID);

        $groups = [];
        foreach ($this->_authgroups as $group) {
            if (!$allowedOnly || $group->isAllowed())
                $groups[] = $group;
        }
        return $groups;
    }

    /**
     * Get access lists
     * return \admin\Model\AccessList[]
     */
    function getAccessLists()
    {
        if ($this->_accesslists === null)
            $this->_accesslists = \admin\model\AccessList::findByCharacter($this);

        return $this->_accesslists;
    }


    function isAuthorized($reset=false)
    {
        \AppRoot::debug("isAuthorized(".$this->name.",".$reset.")");
        $this->getAuthStatus($reset);
        return $this->isAuthorized;
    }

    /**
     * Get character corporation
     * @return \eve\model\Corporation
     */
    function getCorporation()
    {
        if ($this->_corporation == null)
            $this->_corporation = \eve\model\Corporation::getCorporationByID($this->corporationID);

        return $this->_corporation;
    }

    /**
     * Get user
     * @return \users\model\User|null
     */
    function getUser()
    {
        if ($this->_user === null && $this->userID > 0)
            $this->_user = \users\model\User::findByID($this->userID);

        return $this->_user;
    }

    /**
     * Get CREST Token
     * @return \sso\model\Token|null
     */
    function getToken()
    {
        if ($this->_token === null)
            $this->_token = \sso\model\Token::findOne(["tokentype" => "character", "tokenid" => $this->id]);

        return $this->_token;
    }

    /**
     * Import data
     */
    function importData()
    {
        try {
            /** Gebruikt reguliere xml api. Scheelt weer crest-requests, rate-limits enzo */
            $charController = new \eve\controller\Character();
            $character = $charController->importCharacter($this->id);

            // Check corp
            $corpController = new \eve\controller\Corporation();
            $corpController->importCorporation($character->corporationID);
        }
        catch (\Exception $e)
        {
            echo "An error occured while importing data from the xml api..<br />".$e->getMessage();
        }
    }






    /**
     * Find all
     * @param array $conditions
     * @return Character[]
     */
    public static function findAll($conditions=[])
    {
        $query = [];
        $params = [];
        foreach ($conditions as $var => $val) {
            $query[] = $var." = ?";
            $params[] = $val;
        }

        $characters = [];
        if ($results = \MySQL::getDB()->getRows("select * from characters ".((count($query)>0)?"where ".implode(" and ", $query):"")." order by name", $params)) {
            foreach ($results as $result) {
                $char = new static();
                $char->load($result);
                $characters[] = $char;
            }
        }
        return $characters;
    }

    /**
     * Find character by ID
     * @param $characterID
     * @return \eve\model\Character|null
     */
    public static function findByID($characterID)
    {
        if ($result = \MySQL::getDB()->getRow("select * from characters where id = ?", [$characterID])) {
            $char = new \eve\model\Character($characterID);
            $char->load($result);
            return $char;
        }
        return null;
    }
}