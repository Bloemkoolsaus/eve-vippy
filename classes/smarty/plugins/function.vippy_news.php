<?php

function smarty_function_vippy_news($params)
{
    if (!isset($params['month'])) {
        throw new \RuntimeException('news article month/year required');
    }
    if (!isset($params['article'])) {
        throw new \RuntimeException('news article name required');
    }

    // Zoek news article
    foreach (\vippy\model\News::findAll(["name" => $params["article"]]) as $article) {
        if (date("Ym", strtotime($article->newsdate)) == $params["month"]) {
            return "<a href='#' onclick='showNewsArticle($article->id); return false;'>".$article->title."</a>";
        }
    }

    return "";
}
