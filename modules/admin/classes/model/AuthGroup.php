<?php
namespace admin\model;

class AuthGroup
{
    public $id = 0;
    public $name;
    public $contactID;
    public $deleted = false;

    private $config = null;
    private $corporations = null;
    private $alliances = null;
    private $allowedCorporations = null;

    private $modules = null;
    private $chains = null;
    private $subscriptions = null;
    private $payments = null;
    private $usergroups = null;
    private $contactUser = null;
    private $_allowedUsers;

    function __construct($id=false)
    {
        if ($id) {
            $this->id = $id;
            $this->load();
        }
    }

    private function getCacheFilename()
    {
        return "authgroups/".$this->id.".json";
    }

    private function loadFromCache()
    {
        if ($cache = \Cache::file()->get($this->getCacheFilename()))
        {
            $result = json_decode($cache, true);
            $this->load($result);

            if (isset($result["corporations"])) {
                $this->corporations = array();
                foreach ($result["corporations"] as $corpData) {
                    $corporation = new \eve\model\Corporation();
                    $corporation->load($corpData);
                    $this->corporations[] = $corporation;
                }
            }

            if (isset($result["alliances"])) {
                $this->alliances = array();
                foreach ($result["alliances"] as $allyData) {
                    $alliance = new \eve\model\Alliance();
                    $alliance->load($allyData);
                    $this->alliances[] = $alliance;
                }
            }

            if (isset($result["allowed"])) {
                $this->allowedCorporations = array();
                foreach ($result["allowed"] as $corpData) {
                    $corporation = new \eve\model\Corporation();
                    $corporation->load($corpData);
                    $this->allowedCorporations[] = $corporation;
                }
            }

            return true;
        }

        return false;
    }

    private function saveToCache($data)
    {
        $data["corporations"] = array();
        foreach ($this->getCorporations() as $corp) {
            $data["corporations"][] = [
                "id" => $corp->id,
                "ticker" => $corp->ticker,
                "name" => $corp->name,
                "ceo" => $corp->ceoID,
                "allianceid" => $corp->allianceID,
                "updatedate" => $corp->updateDate
            ];
        }

        $data["alliances"] = array();
        foreach ($this->getAlliances() as $ally) {
            $data["alliances"][] = [
                "id" => $ally->id,
                "ticker" => $ally->ticker,
                "name" => $ally->name
            ];
        }

        $data["allowed"] = array();
        foreach ($this->getAllowedCorporations() as $corp)
        {
            $data["allowed"][] = [
                "id" => $corp->id,
                "ticker" => $corp->ticker,
                "name" => $corp->name,
                "ceo" => $corp->ceoID,
                "allianceid" => $corp->allianceID,
                "updatedate" => $corp->updateDate
            ];
        }

        \Cache::file()->set($this->getCacheFilename(), json_encode($data));
    }

    function load($result=false)
    {
        if (!$result) {
            // Eerst in cache kijken
            if (!$this->loadFromCache()) {
                $result = \MySQL::getDB()->getRow("SELECT * FROM user_auth_groups WHERE id = ?", array($this->id));
                $this->saveToCache($result);
            }
        }

        if ($result) {
            $this->id = $result["id"];
            $this->name = $result["name"];
            $this->contactID = $result["contactuserid"];
            $this->deleted = ($result["deleted"]>0)?true:false;
        }
    }

    function store()
    {
        $data = [
            "name"	=> $this->name,
            "contactuserid" => $this->contactID,
            "deleted" => ($this->deleted)?1:0
        ];
        if ($this->id > 0)
            $data["id"] = $this->id;

        $result = \MySQL::getDB()->updateinsert("user_auth_groups", $data, ["id" => $this->id]);
        if ($this->id == 0)
            $this->id = $result;


        if ($this->alliances !== null) {
            \MySQL::getDB()->delete("user_auth_groups_alliances", ["authgroupid" => $this->id]);
            foreach ($this->getAlliances() as $alliance) {
                \MySQL::getDB()->insert("user_auth_groups_alliances", ["authgroupid" => $this->id, "allianceid" => $alliance->id]);
            }
        }

        if ($this->corporations !== null) {
            \MySQL::getDB()->delete("user_auth_groups_corporations", ["authgroupid" => $this->id]);
            foreach ($this->getCorporations() as $corp) {
                \MySQL::getDB()->insert("user_auth_groups_corporations", ["authgroupid" => $this->id, "corporationid" => $corp->id]);
            }
        }

        if ($this->config !== null) {
            \MySQL::getDB()->delete("user_auth_group_config", ["authgroupid" => $this->id]);
            foreach ($this->config as $var => $val) {
                \MySQL::getDB()->insert("user_auth_group_config", [
                    "authgroupid" => $this->id,
                    "var" => $var,
                    "val" => $val
                ]);
            }
        }

        // Reset cache
        \Cache::file()->remove($this->getCacheFilename());
        foreach ($this->getAllowedCorporations() as $corp) {
            foreach (\users\model\User::getUsersByCorporation($corp->id) as $user) {
                $user->resetCache();
            }
        }
    }

    function delete()
    {
        $this->deleted = true;
        $this->store();
    }

    /**
     * Get contact user
     * @return \users\model\User|null
     */
    function getContactUser()
    {
        if ($this->contactUser === null)
        {
            if (!$this->contactID) {
                foreach ($this->getAllowedUsers() as $user) {
                    if ($user->isAdmin()) {
                        if (!$this->contactID || $this->contactID > $user->id)
                            $this->contactID = $user->id;
                    }
                }
                $this->store();
            }

            $this->contactUser = new \users\model\User($this->contactID);
        }

        return $this->contactUser;
    }

    function getLastActiveDate()
    {
        $date = 0;
        foreach ($this->getChains() as $chain) {
            if (strtotime($date) < strtotime($chain->lastActive))
                $date = $chain->lastActive;
        }
        return $date;
    }

    /**
     * Get chains
     * @return \scanning\model\Chain[]
     */
    function getChains()
    {
        if ($this->chains === null)
            $this->chains = \scanning\model\Chain::getChainsByAuthgroup($this->id);

        return $this->chains;
    }

    /**
     * Get allowed corporations
     * @return \eve\model\Corporation[]
     */
    function getAllowedCorporations()
    {
        if ($this->allowedCorporations === null)
        {
            $this->allowedCorporations = [];
            foreach ($this->getCorporations() as $corp) {
                if ($corp->isNPC())
                    continue;
                $this->allowedCorporations[$corp->id] = $corp;
            }
            foreach ($this->getAlliances() as $ally) {
                foreach ($ally->getCorporations() as $corp) {
                    if ($corp->isNPC())
                        continue;
                    $this->allowedCorporations[$corp->id] = $corp;
                }
            }
        }

        return $this->allowedCorporations;
    }


    /**
     * Get corporations
     * @return \eve\model\Corporation[]
     */
    function getCorporations()
    {
        if ($this->corporations === null)
        {
            $this->corporations = [];
            if ($results = \MySQL::getDB()->getRows("SELECT	c.*
                                                    FROM	corporations c
                                                        INNER JOIN user_auth_groups_corporations a ON a.corporationid = c.id
                                                    WHERE	a.authgroupid = ?"
                                            , array($this->id)))
            {
                foreach ($results as $result)
                {
                    $corp = new \eve\model\Corporation();
                    $corp->load($result);
                    $this->addCorporation($corp);
                }
            }
        }

        return $this->corporations;
    }

    /**
     * Add corporation
     * @param \eve\model\Corporation $corp
     */
    function addCorporation(\eve\model\Corporation $corp)
    {
        if ($this->corporations === null)
            $this->getCorporations();

        if (!$corp->isNPC())
            $this->corporations[] = $corp;
    }

    /**
     * Add corporation by id
     * @param integer $corporationID
     */
    function addCorporationById($corporationID)
    {
        $corp = new \eve\model\Corporation($corporationID);
        $this->addCorporation($corp);
    }

    /**
     * Remove corporation
     * @param integer $corporationID
     */
    function removeCorporation($corporationID)
    {
        foreach ($this->getCorporations() as $key => $corp)
        {
            if ($corp->id == $corporationID)
                unset($this->corporations[$key]);
        }
    }


    /**
     * Get alliances
     * @return \eve\model\Alliance[]
     */
    function getAlliances()
    {
        if ($this->alliances === null)
        {
            \AppRoot::debug("AuthGroup()->getAlliances()", true);
            $this->alliances = array();
            if ($results = \MySQL::getDB()->getRows("SELECT	c.*
                                                    FROM	alliances c
                                                        INNER JOIN user_auth_groups_alliances a ON a.allianceid = c.id
                                                    WHERE	a.authgroupid = ?"
                                            , array($this->id)))
            {
                foreach ($results as $result)
                {
                    $ally = new \eve\model\Alliance();
                    $ally->load($result);
                    $this->alliances[] = $ally;
                }
            }
        }

        return $this->alliances;
    }

    /**
     * Add alliance
     * @param \eve\model\Alliance $ally
     */
    function addAlliance(\eve\model\Alliance $ally)
    {
        if ($this->alliances === null)
            $this->getAlliances();

        $this->alliances[] = $ally;
    }

    /**
     * Add alliance by id
     * @param integer $allianceID
     */
    function addAllianceById($allianceID)
    {
        $ally = new \eve\model\Alliance($allianceID);
        $this->addAlliance($ally);
    }

    /**
     * Remove alliance
     * @param integer $allianceID
     */
    function removeAlliance($allianceID)
    {
        foreach ($this->getAlliances() as $key => $ally)
        {
            if ($allianceID == $ally->id)
                unset($this->alliances[$key]);
        }
    }


    /**
     * Get available modules
     * @return array
     */
    function getModules()
    {
        if ($this->modules === null)
        {
            $this->modules = array();
            if ($results = \MySQL::getDB()->getRows("SELECT	*
                                                    FROM	user_auth_groups_modules
                                                    WHERE	authgroupid = ?"
                                            , array($this->id)))
            {
                foreach ($results as $result)
                {
                    $this->modules[] = $result["module"];
                }
            }
        }

        return $this->modules;
    }

    /**
     * Allowed?
     * @return boolean
     */
    function isAllowed()
    {
        \AppRoot::debug("AuthGroup->isAllowed([".$this->id."] ".$this->name.")");

        // Check for active subscriptions
        foreach ($this->getSubscriptions() as $subscription) {
            if ($subscription->isActive())
                return true;
        }

        return true;
    }

    function isActive()
    {
        if (count($this->getAllowedUsers()) == 0)
            return false;

        if (strtotime("now")-strtotime($this->getLastActiveDate()) > 5184000)   // 2 maanden
            return false;

        return true;
    }

    /**
     * Has access to module?
     * @param string $name
     * @return boolean
     */
    function hasModule($name)
    {
        foreach ($this->getModules() as $module)
        {
            if ($module == $name)
                return true;
        }
        return false;
    }

    function clearConfig()
    {
        $this->config = [];
    }

    private function fetchConfig()
    {
        $this->clearConfig();
        if ($results = \MySQL::getDB()->getRows("SELECT *
                                                FROM 	user_auth_group_config
                                                WHERE 	authgroupid = ?", array($this->id)))
        {
            foreach ($results as $result) {
                $this->setConfig($result["var"],$result["val"]);
            }
        }
    }

    /**
     * Get config option
     * @param string $option
     * @return string|false
     */
    function getConfig($option)
    {
        if ($this->config === null)
            $this->fetchConfig();

        if (isset($this->config[$option]))
            return $this->config[$option];

        return false;
    }

    /**
     * Set config
     * @param $var
     * @param $val
     */
    function setConfig($var, $val)
    {
        if ($this->config === null)
            $this->fetchConfig();

        $this->config[$var] = $val;
    }

    /**
     * Get allowed users
     * @return \users\model\User[]
     */
    function getAllowedUsers()
    {
        if ($this->_allowedUsers === null)
        {
            $corporations = $this->getCorporations();
            foreach ($this->getAlliances() as $alliance) {
                $corporations = array_merge($corporations, $alliance->getCorporations());
            }

            $this->_allowedUsers = [];
            foreach ($corporations as $corp) {
                foreach (\users\model\User::getUsersByCorporation($corp->id) as $user) {
                    if ($user->isAuthorized())
                        $this->_allowedUsers[$user->id] = $user;
                }
            }
        }

        return $this->_allowedUsers;
    }

    /**
     * Get active and allowed users
     * @param null $date
     * @return \users\model\User[]
     */
    function getActiveUsers($date=null)
    {
        $users = [];

        if (!$date)
        {
            /**
             * Huidige actieve users
             */

            // Alle toegestane users
            $ids = [];
            foreach ($this->getAllowedUsers() as $user) {
                $ids[] = $user->id;
            }

            // Wie daarvan zijn ingelogd geweest?
            if (count($ids) > 0) {
                if ($results = \MySQL::getDB()->getRows("select u.*
                                                        from    users u
                                                          inner join user_log l on l.userid = u.id
                                                        where   l.what = 'login'
                                                        and     l.logdate between ? and ?
                                                        and     u.id in (".implode(",", $ids).")
                                                        group by u.id"
                                    , [date("Y-m-d", mktime(0,0,0,date("m"), date("d")-30, date("Y"))),
                                       date("Y-m-d", mktime(0,0,0,date("m"), date("d"), date("Y")))]))
                {
                    foreach ($results as $result) {
                        $user = new \users\model\User();
                        $user->load($result);
                        $users[] = $user;
                    }
                }
            }
        }
        else
        {
            /**
             * Actieve users in periode
             */
            if ($results = \MySQL::getDB()->getRows("select u.*
                                                     from   users u
                                                        inner join stats_users s on u.id = s.userid
                                                     where  s.authgroupid = ?
                                                     and    s.year = ? and s.month = ?
                                                     group by u.id"
                                , [$this->id, date("Y", strtotime($date)), date("m", strtotime($date))]))
            {
                foreach ($results as $result) {
                    $user = new \users\model\User();
                    $user->load($result);
                    $users[] = $user;
                }
            }
        }

        return $users;
    }

    /**
     * Get users that have been manually granted access
     * @return \users\model\User[]
     */
    function getGrantedUsers()
    {
        $users = [];
        if ($results = \MySQL::getDB()->getRows("select u.*
                                                 from   users u
                                                    inner join users_auth_groups_users g on g.userid = u.id
                                                 where  g.authgroupid = ?"
                                        , [$this->id]))
        {
            foreach ($results as $result)
            {
                $user = new \users\model\User();
                $user->load($result);
                $users[] = $user;
            }
        }

        return $users;
    }

    /**
     * Mag deze user deze auth-group beheren?
     * @param \users\model\User $user
     * @return boolean
     */
    function getMayAdmin(\users\model\User $user=null)
    {
        if ($user == null)
            $user = \User::getUSER();

        if ($user->getIsSysAdmin())
            return true;

        foreach ($user->getAuthGroupsAdmins() as $group)
        {
            if ($group->id == $this->id)
                return true;
        }

        return false;
    }

    /**
     * Get subscriptions
     * @return \admin\model\Subscription[]
     */
    function getSubscriptions()
    {
        if ($this->subscriptions === null)
            $this->subscriptions = \admin\model\Subscription::getSubscriptionsByAuthgroup($this->id);

        return $this->subscriptions;
    }

    /**
     * Get active subscription
     * @param null $onDate
     * @return Subscription|NULL
     */
    function getSubscription($onDate=null)
    {
        foreach ($this->getSubscriptions() as $sub) {
            if ($sub->isActive($onDate))
                return $sub;
        }
        return null;
    }

    /**
     * Get payments
     * @return \admin\model\SubscriptionTransaction[]
     */
    function getPayments()
    {
        if ($this->payments === null) {
            $conditions = ["authgroupid" => $this->id, "approved" => 1, "deleted" => 0];
            $this->payments = \admin\model\SubscriptionTransaction::findAll($conditions, ["transactiondate desc"]);
        }

        return $this->payments;
    }

    /**
     * Get usergroups
     * @return \users\model\UserGroup[]
     */
    function getUsergroups()
    {
        if ($this->usergroups === null)
            $this->usergroups = \users\model\UserGroup::findAll(["authgroupid" => $this->id]);

        return $this->usergroups;
    }

    function getClosestSystems()
    {
        return \map\model\ClosestSystem::getClosestSystemsBySystemID();
    }





    /**
     * Get authgroups
     * @return  \admin\model\AuthGroup[]
     */
    public static function getAuthGroups()
    {
        $authgroups = [];
        if ($results = \MySQL::getDB()->getRows("select * from user_auth_groups order by name")) {
            foreach ($results as $result) {
                $group = new \admin\model\AuthGroup();
                $group->load($result);
                $authgroups[] = $group;
            }
        }
        return $authgroups;
    }

    /**
     * Get authgroups by corporation
     * @param integer $corporationID
     * @return \admin\model\AuthGroup[]
     */
    public static function getAuthgroupsByCorporation($corporationID)
    {
        $authgroups = array();
        if ($results = \MySQL::getDB()->getRows("SELECT	g.*
                                                FROM    user_auth_groups g
                                                    INNER JOIN user_auth_groups_corporations c ON c.authgroupid = g.id
                                                WHERE   c.corporationid = ?
                                            UNION
                                                SELECT  g.*
                                                FROM    user_auth_groups g
                                                    INNER JOIN user_auth_groups_alliances a ON a.authgroupid = g.id
                                                    INNER JOIN corporations c ON c.allianceid = a.allianceid
                                                WHERE   c.id = ?
                                            GROUP BY g.id"
            , array($corporationID, $corporationID)))
        {
            foreach ($results as $result)
            {
                $group = new \admin\model\AuthGroup();
                $group->load($result);
                $authgroups[] = $group;
            }
        }
        return $authgroups;
    }

    /**
     * Get authgroups by corporation
     * @param integer $allianceID
     * @return \admin\model\AuthGroup[]
     */
    public static function getAuthgroupsByAlliance($allianceID)
    {
        $authgroups = array();
        if ($results = \MySQL::getDB()->getRows("SELECT	g.*
                                                FROM    user_auth_groups g
                                                    INNER JOIN user_auth_groups_alliances a ON a.authgroupid = g.id
                                                WHERE   a.allianceid = ?
                                                GROUP BY g.id"
                                , array($allianceID)))
        {
            foreach ($results as $result)
            {
                $group = new \admin\model\AuthGroup();
                $group->load($result);
                $authgroups[] = $group;
            }
        }
        return $authgroups;
    }
}