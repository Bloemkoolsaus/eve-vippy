<?php
namespace crest;

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

    function get($url, $params = array())
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
}