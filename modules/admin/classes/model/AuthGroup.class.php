<?php
namespace admin\model
{
	class AuthGroup
	{
		public $id = 0;
		public $name;
		public $mainChainID;

		private $config = null;
		private $corporations = null;
		private $alliances = null;
		private $modules = null;
		private $chains = null;
		private $subscriptions = null;
		private $payments = null;

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
				$result = \MySQL::getDB()->getRow("SELECT * FROM user_auth_groups WHERE id = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["id"];
				$this->name = $result["name"];
				$this->mainChainID = $result["mainchain"];
			}
		}

		function store()
		{
			$data = array("name"	=> $this->name,
						"mainchain"	=> $this->mainChainID);
			if ($this->id > 0)
				$data["id"] = $this->id;

			$result = \MySQL::getDB()->updateinsert("user_auth_groups", $data, array("id" => $this->id));
			if ($this->id == 0)
				$this->id = $result;


			if ($this->alliances !== null)
			{
				\MySQL::getDB()->delete("user_auth_groups_alliances", array("authgroupid" => $this->id));
				foreach ($this->getAlliances() as $alliance) {
					\MySQL::getDB()->insert("user_auth_groups_alliances", array("authgroupid" => $this->id, "allianceid" => $alliance->id));
				}
			}

			if ($this->corporations !== null)
			{
				\MySQL::getDB()->delete("user_auth_groups_corporations", array("authgroupid" => $this->id));
				foreach ($this->getCorporations() as $corp) {
					\MySQL::getDB()->insert("user_auth_groups_corporations", array("authgroupid" => $this->id, "corporationid" => $corp->id));
				}
			}
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
			$corporations = array();
			foreach ($this->getCorporations() as $corp) {
				$corporations[$corp->id] = $corp;
			}
			foreach ($this->getAlliances() as $ally) {
				foreach ($ally->getCorporations() as $corp) {
					$corporations[$corp->id] = $corp;
				}
			}
			return $corporations;
		}


		/**
		 * Get corporations
		 * @return \eve\model\Corporation[]
		 */
		function getCorporations()
		{
			if ($this->corporations === null)
			{
				$this->corporations = array();
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
			// Check for active subscriptions
			foreach ($this->getSubscriptions() as $subscription)
			{
				if ($subscription->isActive())
					return true;
			}

			return false;
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

		/**
		 * Get config option
		 * @param string $option
		 * @return string|false
		 */
		function getConfig($option)
		{
			if ($this->config === null)
			{
				$this->config = array();
				if ($results = \MySQL::getDB()->getRows("SELECT *
														FROM 	user_auth_group_config
														WHERE 	authgroupid = ?", array($this->id)))
				{
					foreach ($results as $result) {
						$this->config[$result["var"]] = $result["val"];
					}
				}
			}

			if (isset($this->config[$option]))
				return $this->config[$option];

			return false;
		}

		/**
		 * Get allowed users
		 * @return \users\model\User[]
		 */
		function getAllowedUsers()
		{
			$corporations = $this->getCorporations();
			foreach ($this->getAlliances() as $alliance) {
				$corporations = array_merge($corporations, $alliance->getCorporations());
			}

			$users = array();
			foreach ($corporations as $corp)
			{
				foreach (\users\model\User::getUsersByCorporation($corp->id) as $user)
				{
					if ($user->isAuthorized())
						$users[$user->id] = $user;
				}
			}
			return $users;
		}

		/**
		 * Mag deze user deze auth-group beheren?
		 * @param \users\model\User $user
		 * @return boolean
		 */
		function getMayAdmin(\users\model\User $user)
		{
			if (\User::getUSER()->getIsSysAdmin())
				return true;

			foreach (\User::getUSER()->getAuthGroupsAdmins() as $group)
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
		 * @return \admin\model\Subscription|NULL
		 */
		function getSubscription()
		{
			foreach ($this->getSubscriptions() as $sub)
			{
				if ($sub->isActive())
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
			if ($this->payments === null)
				$this->payments = \admin\model\SubscriptionTransaction::getTransactionsByAuthgroup($this->id);

			return $this->payments;
		}





		/**
		 * Get authgroups
		 * @return  \admin\model\AuthGroup[]
		 */
		public static function getAuthGroups()
		{
			$authgroups = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM user_auth_groups ORDER BY name"))
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
	}
}
?>