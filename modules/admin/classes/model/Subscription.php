<?php
namespace admin\model;

class Subscription extends \Model
{
    protected $_table = "vippy_subscriptions";

    public $id = 0;
    public $authgroupID = 0;
    public $description;
    public $amount = 0;
    public $fromdate;
    public $tilldate;
    public $resetBalance = false;

    private $_authGroup = null;

    function store()
    {
        if ($this->fromdate)
            $this->fromdate = date("Y-m-d", strtotime($this->fromdate))." 00:00:00";
        if ($this->tilldate)
            $this->tilldate = date("Y-m-d", strtotime($this->tilldate))." 23:59:59";

        $new = (!$this->id)?true:false;
        parent::store();

        if ($new)
        {
            // Deze was nieuw. Check of er nog lopende zijn, zo ja, laat die aflopen!
            foreach (self::getSubscriptionsByAuthgroup($this->authgroupID) as $subscription) {
                if (strtotime($subscription->fromdate) < strtotime($this->fromdate)) {
                    if (!$subscription->tilldate) {
                        $subscription->tilldate = $this->fromdate;
                        $subscription->store();
                    }
                }
            }
        }
    }

    function isActive($onDate=null)
    {
        $onDate = ($onDate) ? strtotime($onDate) : strtotime("now");
        \AppRoot::debug("Subscription->isActive($this->fromdate,$this->tilldate)");

        if ($this->fromdate != null) {
            if (strtotime($this->fromdate) > 0 && strtotime($this->fromdate) >= $onDate) {
                \AppRoot::debug("subscription not yet started");
                return false;
            }
        }
        if ($this->tilldate != null) {
            if (strtotime($this->tilldate) > 0 && strtotime($this->tilldate) <= $onDate) {
                \AppRoot::debug("subscription expired");
                return false;
            }
        }

        \AppRoot::debug("Active!");
        return true;
    }

    /**
     * Get authgroup
     * @return \admin\model\AuthGroup|null
     */
    function getAuthgroup()
    {
        if ($this->_authGroup === null && $this->authgroupID > 0)
            $this->_authGroup = new \admin\model\AuthGroup($this->authgroupID);

        return $this->_authGroup;
    }

    function getAmount()
    {
        return $this->amount * 100000000;
    }

    /**
     * How many has been payed this month
     * @param null $date
     * @return number
     */
    function getPayed($date=null)
    {
        $totalPayed = 0;
        if ($date == null)
            $date = date("Y-m-d");

        if ($this->getAuthgroup() != null) {
            $payments = array();
            foreach ($this->getAuthgroup()->getPayments() as $payment) {
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
        if ($results = \MySQL::getDB()->getRows("SELECT * FROM vippy_subscriptions ORDER BY tilldate desc, fromdate desc")) {
            foreach ($results as $result) {
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
            foreach ($results as $result) {
                $sub = new \admin\model\Subscription();
                $sub->load($result);
                $subscriptions[] = $sub;
            }
        }
        return $subscriptions;
    }
}