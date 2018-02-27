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

    /**
     * Set token
     * @param \sso\model\Token $token
     */
    function setToken(\sso\model\Token $token)
    {
        $this->token = $token;
    }

    function get($url, $params=[])
    {
        if (!$this->isOnline()) {
            return;
        }

        // Check log/cache
        $log = \esi\model\Log::findOne(["url" => $this->baseURL.$url, "requesttype" => "get", "httpstatus" => 200]);
        if ($log) {
            if (strtotime($log->expireDate) > strtotime("now")) {
                $this->result = json_decode($log->response);
                $this->httpStatus = $log->httpStatus;
                return;
            } else
                $log->delete();
        }
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
        if (!$this->isOnline()) {
            return;
        }

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
        if (!is_object($result) && !is_array($result))
            $result = json_decode($result);

        return $result;
    }

    function log()
    {
        $log = new \esi\model\Log();
        $log->url = $this->getRequest()->url;
        $log->requestType = strtolower($this->getRequest()->type);
        $log->requestDate = date("Y-m-d H:i:s");
        $log->response = json_encode($this->result);
        $log->httpStatus = ($this->httpStatus)?$this->httpStatus:null;

        if (isset($this->getRequest()->content)) {
            $log->content = $this->getRequest()->content;
            if (is_object($log->content) || is_array($log->content))
                $log->content = json_encode($log->content);
        }

        $expires = strtotime("now");
        if ($this->getResultHeader("Date") && $this->getResultHeader("Expires")) {
            $expires = strtotime("now")+(strtotime($this->getResultHeader("Expires"))-strtotime($this->getResultHeader("Date")));
        }
        $log->expireDate = date("Y-m-d H:i:s", $expires);
        $log->errorRemain = $this->getResultHeader("X-Esi-Error-Limit-Remain");
        $log->errorReset = $this->getResultHeader("X-Esi-Error-Limit-Reset");
        $log->store();

        \MySQL::getDB()->doQuery("update esi_status
                                  set   errorremain = ".$log->errorRemain.",
                                        errorreset = ".$log->errorRemain.",
                                        updatedate = '".date("Y-m-d H:i:s")."'");
        // Check error limit
        if ($log->errorRemain !== null) {
            if ($log->errorRemain < 20) {
                $this->disable();
            }
        }
    }

    function enable()
    {
        \MySQL::getDB()->doQuery("update esi_status set enabled = 1, updatedate = '".date("Y-m-d H:i:s")."'");
    }

    function disable()
    {
        \MySQL::getDB()->doQuery("update esi_status set enabled = 0, updatedate = '".date("Y-m-d H:i:s")."'");
    }

    /**
     * Is the esi api online or otherwise enabled?
     * @return bool
     */
    function isOnline()
    {
        // Check status
        $status = \MySQL::getDB()->getRow("select * from esi_status");
        if ($status) {
            if ($status["enabled"] < 1) {
                if (strtotime($status["updatedate"]) > strtotime("now")-60) {
                    return false;
                } else {
                    $this->enable();
                }
            }
        }
        return true;
    }
}