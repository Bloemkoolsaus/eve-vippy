<?php
namespace crest;

/**
 * Class Api
 * @package crest
 * http://eveonline-third-party-documentation.readthedocs.io/en/latest/crest/intro.html
 *
 *  Caching:
 *      CCP Bartender: https://forums.eveonline.com/default.aspx?g=posts&m=6692497#post6692497
 *    When you make a request to a CREST endpoint, it will return a "cache-control" header telling you how long in
 *    seconds the cache for that endpoint is. Additionally, the "x-cache-status" header will tell you whether your
 *    request was a cache hit or miss.
 */

class Api extends \api\Client
{
    /** @var \crest\model\Token */
    public $token = null;

    function __construct($baseURL=false)
    {
        if (!$baseURL)
            $baseURL = \Config::getCONFIG()->get("crest_url");

        parent::__construct($baseURL);
    }

    function setToken(\crest\model\Token $token)
    {
        $this->token = $token;
    }

    function get($url, $params=[])
    {
        $this->resetheader();
        $this->addHeader("Accept: ".\Config::getCONFIG()->get("crest_accept_version"));

        if ($this->token) {
            if ($this->token->isExpired())
                $this->token->refresh();
            $this->addHeader("Authorization: Bearer ".$this->token->accessToken);
        }

        return parent::get($url, $params);
    }

    function post($url, $params=[])
    {
        $this->resetheader();
        $this->addHeader("Accept: ".\Config::getCONFIG()->get("crest_accept_version"));

        if ($this->token) {
            if ($this->token->isExpired())
                $this->token->refresh();
            $this->addHeader("Authorization: Bearer ".$this->token->accessToken);
        }

        return parent::post($url, $params);
    }

    function getResult()
    {
        $result = parent::getResult();
        if (!is_object($result))
            $result = json_decode($result);

        return $result;
    }
}