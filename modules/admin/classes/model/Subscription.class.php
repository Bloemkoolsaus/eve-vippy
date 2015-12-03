<?php
namespace admin\model
{
	class Subscription
	{
		public $id = 0;
		public $authgroupID;
		public $description;
		public $amount;
		public $fromdate;
		public $tilldate;

		private $authGroup = null;

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
				$result = \MySQL::getDB()->getRow("SELECT * FROM vippy_subscriptions WHERE id = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["id"];
				$this->authgroupID = $result["authgroupid"];
				$this->description = $result["description"];
				$this->amount = $result["amount"];
				$this->fromdate = $result["fromdate"];
				$this->tilldate = $result["tilldate"];
			}
		}

		function store()
		{
			$data = array(	"authgroupid"	=> $this->authgroupID,
							"description"	=> $this->description,
							"amount"		=> $this->amount,
							"fromdate"		=> $this->fromdate,
							"tilldate"		=> $this->tilldate);
			if ($this->id > 0)
				$data["id"] = $this->id;

			$result = \MySQL::getDB()->updateinsert("vippy_subscriptions", $data, array("id" => $this->id));
			if ($this->id == 0)
			{
				$this->id = $result;

				// Deze was nieuw. Check of er nog lopende zijn, zo ja, laat die aflopen!
				foreach (self::getSubscriptionsByAuthgroup($this->authgroupID) as $subscription)
				{
					if (strtotime($subscription->fromdate) < strtotime($this->fromdate))
					{
						if ($subscription->tilldate == null)
						{
							$subscription->tilldate = $this->fromdate;
							$subscription->store();
						}
					}
				}
			}
		}

		function isActive()
		{
			if ($this->fromdate != null) {
				if (strtotime($this->fromdate) > strtotime("now"))
					return false;
			}

			if ($this->tilldate != null) {
				if (strtotime($this->tilldate) < strtotime("now"))
					return false;
			}

			return true;
		}

		/**
		 * Get authgroup
		 * @return \admin\model\AuthGroup|null
		 */
		function getAuthgroup()
		{
			if ($this->authGroup === null && $this->authgroupID > 0)
				$this->authGroup = new \admin\model\AuthGroup($this->authgroupID);

			return $this->authGroup;
		}

		function getAmount()
		{
			return $this->amount * 100000000;
		}

		/**
		 * How many has been payed this month
		 * @return number
		 */
		function getPayed($date=null)
		{
			$totalPayed = 0;
			if ($date == null)
				$date = date("Y-m-d");

			if ($this->getAuthgroup() != null)
			{
				$payments = array();

				foreach ($this->getAuthgroup()->getPayments() as $payment)
				{
					// Alle payments in dezelfde maand als $date
					if (date("Ym", strtotime($payment->date)) == date("Ym", strtotime($date)))
						$payments[] = $payment;
				}

				foreach ($payments as $payment) {
					$totalPayed += $payment->amount;
				}
			}

			return $totalPayed;
		}

		/**
		 * Has been payed?
		 * @param string $date
		 * @return boolean
		 */
		function hasPayed($date=null)
		{
			if ($this->amount <= $this->getPayed($date))
				return true;

			return false;
		}







		/**
		 * Get subscriptions
		 * @return \admin\model\Subscription[]
		 */
		public static function getSubscriptions()
		{
			$subscriptions = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM vippy_subscriptions ORDER BY tilldate desc, fromdate desc"))
			{
				foreach ($results as $result)
				{
					$sub = new \admin\model\Subscription();
					$sub->load($result);
					$subscriptions[] = $sub;
				}
			}
			return $subscriptions;
		}

		/**
		 * Get subscriptions by authgroup
		 * @param integer $authgroupID
		 * @return \admin\model\Subscription[]
		 */
		public static function getSubscriptionsByAuthgroup($authgroupID)
		{
			$subscriptions = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM vippy_subscriptions WHERE authgroupid = ?
													ORDER BY fromdate DESC, tilldate DESC"
									, array($authgroupID)))
			{
				foreach ($results as $result)
				{
					$sub = new \admin\model\Subscription();
					$sub->load($result);
					$subscriptions[] = $sub;
				}
			}
			return $subscriptions;
		}
	}
}
?>