<?php
namespace eve\model;

class Character
{
    public $id = 0;
    public $userID = 0;
    public $name;
    public $corporationID = 0;
    public $isDirector = false;
    public $isCEO = false;
    public $titles = array();
    public $updatedate;
    public $isAuthorized = null;
    public $authMessage = null;

    private $corporation = null;
    private $user = null;

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
            $result = \MySQL::getDB()->getRow("SELECT * FROM characters WHERE id = ?", array($this->id));

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

            // Heeft een geldige CREST token
            /** @var \crest\model\Token $token */
            $token = \crest\model\Token::findAll(["tokentype" => "character", "tokenid" => $this->id]);
            if ($token) {
                \AppRoot::debug("We have an active CREST token for ".$this->name);
                foreach ($this->getAuthGroups() as $group) {
                    \AppRoot::debug($group->name);
                    $this->isAuthorized = true;
                    break;
                }
                if (!$this->isAuthorized)
                    $this->authMessage = "Not a member of an allowed Corporation or Alliance";
            } else
                $this->authMessage = "No valid CREST authentication token";

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
        $groups = [];
        foreach (\admin\model\AuthGroup::getAuthgroupsByCorporation($this->corporationID) as $group) {
            if (!$allowedOnly || $group->isAllowed())
                $groups[] = $group;
        }
        return $groups;
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
        if ($this->corporation == null)
            $this->corporation = \eve\model\Corporation::getCorporationByID($this->corporationID);

        return $this->corporation;
    }

    /**
     * Get user
     * @return \users\model\User|null
     */
    function getUser()
    {
        if ($this->user === null && $this->userID > 0)
            $this->user = new \users\model\User($this->userID);

        return $this->user;
    }




    /**
     * Find all
     * @return \eve\model\Character[]
     */
    public static function findAll()
    {
        $characters = [];
        if ($results = \MySQL::getDB()->getRows("select * from characters order by name")) {
            foreach ($results as $result) {
                $char = new \eve\model\Character();
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