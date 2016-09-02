<?php
namespace crest;

class Api extends \api\Client
{
    function __construct($baseURL=false)
    {
        if (!$baseURL)
            $baseURL = \Config::getCONFIG()->get("crest_url");

        parent::__construct($baseURL);
    }
}