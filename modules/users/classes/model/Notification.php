<?php
namespace users\model;

class Notification extends \Model
{
    public $id = 0;
    public $userID;
    public $type = "notice";
    public $title;
    public $content;
    public $persistant = false;
    public $notifyDate;
    public $expireDate;
    public $readDate;


    function getTitle()
    {
        return $this->title;
    }

    function getContent()
    {
        return nl2br($this->content);
    }

    function store()
    {
        if ($this->notifyDate == null)
            $this->notifyDate = date("Y-m-d H:i:s");

        parent::store();
    }


    /**
     * Get notifications by user
     * @param integer   $userID
     * @param bool      $activeOnly true
     * @return \users\model\Notification[]
     */
    public static function getNotificationsByUser($userID, $activeOnly=true)
    {
        $query = ["(expiredate is null or expiredate < '".date("Y-m-d H:i:s")."')"];
        if ($activeOnly)
            $query[] = "(readdate is null or persistant = 1)";

        $notifications = [];
        if ($results = \MySQL::getDB()->getRows("select *
                                                 from   users_notification
                                                 where  (userid = ? or userid is null)
                                                 and    ".implode(" and ", $query)."
                                                 order by notifydate asc"
                                        , [$userID]))
        {
            foreach ($results as $result) {
                $note = new \users\model\Notification();
                $note->load($result);
                $notifications[] = $note;
            }
        }
        return $notifications;
    }
}
