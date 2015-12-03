<?php
namespace admin\model
{
	class SubscriptionTransaction
	{
		public $id = 0;
		public $authgroupID = 0;
		public $description;
		public $fromCharacterID = 0;
		public $toCharacterID = 0;
		public $amount = 0;
		public $date;

		private $authgroup = null;
		private $fromCharacter = null;
		private $toCharacter = null;

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
				$result = \MySQL::getDB()->getRow("SELECT * FROM vippy_subscriptions_journal WHERE id = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["id"];
				$this->authgroupID = $result["authgroupid"];
				$this->description = $result["description"];
				$this->fromCharacterID = $result["fromcharacterid"];
				$this->toCharacterID = $result["tocharacterid"];
				$this->amount = $result["amount"];
				$this->date = $result["transactiondate"];
			}
		}

		function store()
		{
			$data = array(	"authgroupid"		=> $this->authgroupID,
							"description"		=> $this->description,
							"fromcharacterid"	=> $this->fromCharacterID,
							"tocharacterid"		=> $this->toCharacterID,
							"amount"			=> $this->amount,
							"transactiondate"	=> $this->date);
			if ($this->id > 0)
				$data["id"] = $this->id;

			$result = \MySQL::getDB()->updateinsert("vippy_subscriptions_journal", $data, array("id" => $this->id));
			if ($this->id == 0)
				$this->id = $result;
		}

		/**
		 * Get authgroup
		 * @return \admin\model\AuthGroup
		 */
		function getAuthgroup()
		{
			if ($this->authgroup === null && $this->authgroupID > 0)
				$this->authgroup = new \admin\model\AuthGroup($this->authgroupID);

			return $this->authgroup;
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

			if ($character != null && $character->getUser() != null)
			{
				\AppRoot::debug($character->name);
				if (count($character->getUser()->getAuthGroups()) == 1)
				{
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
			if ($this->fromCharacter === null)
				$this->fromCharacter = new \eve\model\Character($this->fromCharacterID);

			return $this->fromCharacter;
		}

		/**
		 * Get to character
		 * @return \eve\model\Character
		 */
		function getToCharacter()
		{
			if ($this->toCharacter === null)
				$this->toCharacter = new \eve\model\Character($this->toCharacterID);

			return $this->toCharacter;
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




		/**
		 * Get transactions by authgroup
		 * @param integer $authgroupID
		 * @return \admin\model\SubscriptionTransaction[]
		 */
		public static function getTransactionsByAuthgroup($authgroupID)
		{
			$transactions = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM vippy_subscriptions_journal
													WHERE authgroupid = ? ORDER BY transactiondate DESC"
										, array($authgroupID)))
			{
				foreach ($results as $result)
				{
					$transaction = new \admin\model\SubscriptionTransaction();
					$transaction->load($result);
					$transactions[] = $transaction;
				}
			}
			return $transactions;
		}
	}
}
?>