<?php
namespace vippy\view;

class News
{
    function getUnread($arguments=[])
    {
        $news = \vippy\model\News::findAllUnread();

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("news", $news);
        return $tpl->fetch("vippy/news/popup");
    }

    function getArticle($arguments=[])
    {
        $news = [\vippy\model\News::findById(array_shift($arguments))];

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("news", $news);
        return $tpl->fetch("vippy/news/popup");
    }
}