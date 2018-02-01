<?php
namespace esi;

class Api extends \api\Client
{
    /** @var \sso\model\Token */
    public $token = null;

    function __construct($baseURL=false)
    {
        if (!$baseURL)
            $baseURL = \Config::getCONFIG()->get("esi_url");

        parent::__construct($baseURL);
    }

    function setToken(\crest\model\Token $token)
    {
        $this->token = $token;
    }

    function get($url, $params=[])
    {
        $this->resetheader();
        if ($this->token) {
            if ($this->token->isExpired())
                $this->token->refresh();
            $this->addHeader("Authorization: Bearer ".$this->token->accessToken);
        }

        parent::get($url, $params);
        $this->log();
    }

    function post($url, $params=[])
    {
        $this->resetheader();

        if ($this->token) {
            if ($this->token->isExpired())
                $this->token->refresh();
            $this->addHeader("Authorization: Bearer ".$this->token->accessToken);
        }

        parent::post($url, $params);
        $this->log();
    }

    function getResult()
    {
        $result = parent::getResult();
        if (!is_object($result))
            $result = json_decode($result);

        return $result;
    }

    function log()
    {
        $content = null;
        if (isset($this->getRequest()->content)) {
            $content = $this->getRequest()->content;
            if (is_object($content) || is_array($content))
                $content = json_encode($content);
        }

        $response = [
            "content" => $this->getResult(),
            "headers" => $this->getResultHeaders()
        ];
        if (is_object($response) || is_array($response))
            $response = json_encode($response);

        $expires = "now";
        if ($this->getResultHeader("Date") && $this->getResultHeader("Expires")) {
            $expires = strtotime("now")+(strtotime($this->getResultHeader("Expires"))-strtotime($this->getResultHeader("Date")));
        }

        \MySQL::getDB()->insert("esi_log", [
            "requesttype" => strtolower($this->getRequest()->type),
            "url" => $this->getRequest()->url,
            "requestdate" => date("Y-m-d H:i:s"),
            "expiredate" => date("Y-m-d H:i:s", $expires),
            "httpstatus" => ($this->httpStatus)?$this->httpStatus:null,
            "content" => $content,
            "response" => $response
        ]);
    }
}