<?php
namespace vippy\model;

class News extends \Model
{
    public $id;
    public $name;
    public $title;
    public $newsdate;

    function getTemplate()
    {
        return "vippy/news/".date("Ym", strtotime($this->newsdate))."/".$this->name;
    }

    function markRead($userID=null)
    {
        if (!$userID) {
            $userID = (\User::getUSER()) ? \User::getUSER()->id : null;
        }

        if ($userID) {
            \MySQL::getDB()->updateinsert("vippy_news_read", [
                "userid" => $userID,
                "newsid" => $this->id,
                "readdate" => date("Y-m-d H:i:s")
            ], [
                "userid" => $userID,
                "newsid" => $this->id,
            ]);
        }
    }


    /**
     * Find unread news items for user
     * @param null $userID
     * @return \vippy\model\News[]
     */
    public static function findAllUnread($userID=null)
    {
        if (!$userID)
            $userID = \User::getUSER()->id;

        $articles = [];
        if ($results = \MySQL::getDB()->getRows("select *
                                                from    vippy_news n
                                                    left join vippy_news_read r on r.newsid = n.id and r.userid = ?
                                                where   r.newsid is null"
                                            , [$userID]))
        {
            foreach ($results as $result) {
                $news = new \vippy\model\News();
                $news->load($result);
                $articles[] = $news;
            }
        }
        return $articles;
    }
}