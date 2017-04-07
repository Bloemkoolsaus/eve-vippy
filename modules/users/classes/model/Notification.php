<?php
namespace users\model;

class Notification extends \Model
{
    public $id = 0;
    public $userID = 0;
    public $type = "notice";
    public $title;
    public $content;
    public $notifyDate;
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
     * @param bool|true $activeOnly
     * @return \users\model\Notification[]
     */
    public static function getNotificationsByUser($userID, $activeOnly=true)
    {
        $notifications = [];
        if ($results = \MySQL::getDB()->getRows("SELECT *
                                                FROM    users_notification
                                                WHERE   (userid = ? OR userid IS NULL)
                                                ".(($activeOnly)?" AND readdate IS NULL":"")."
                                                ORDER BY notifydate"
                                        , array($userID)))
        {
            foreach ($results as $result)
            {
                $note = new \users\model\Notification();
                $note->load($result);
                $notifications[] = $note;
            }
        }
        return $notifications;
    }
}