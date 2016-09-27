<?php
namespace eve\controller;

class API
{
    public $cancelOnCache = false;

    private $url;
    private $params = array();
    private $errors = array();
    private $cacheDir = null;
    private $cachedUntil = null;

    function __construct()
    {
        $this->cacheDir = "logs/api/";
    }

    function setKeyID($id)
    {
        $this->setParam("keyid", $id);
    }
    function setvCode($code)
    {
        $this->setParam("vcode", $code);
    }
    function setCharacterID($id)
    {
        $this->setParam("characterid", $id);
    }
    function setParam($param,$value)
    {
        $this->params[$param] = $value;
    }

    /**
     * Call api
     * @param $page
     * @return \stdClass|bool false on failure
     * @throws \Exception
     */
    function call($page)
    {
        $this->url = \Config::getCONFIG()->get("eve_api_url").trim($page,"/");

        $i = 0;
        foreach ($this->params as $param => $value) {
            $this->url .= (($i==0)?"?":"&").$param."=".$value;
            $i++;
        }

        // Check cache.
        \AppRoot::doCliOutput("Call eve-api: ".$this->url);
        if ($cache = \MySQL::getDB()->getRow("SELECT * FROM api_log WHERE url = ? AND cachedate > ?", array($this->url, date("Y-m-d H:i:s"))))
        {
            \AppRoot::debug("   * Cached on ".$cache["cachedate"]);
            // Haal uit cache.
            if (!$this->cancelOnCache) {
                $cacheFile = $this->cacheDir.$cache["id"].".xml";
                if (file_exists($cacheFile)) {
                    \AppRoot::doCliOutput("   * Return cache: ".$cacheFile);
                    $cachedXML = file_get_contents($cacheFile);
                    return new \SimpleXMLElement($cachedXML);
                }
                else
                    \AppRoot::doCliOutput("   * Cache file not found");
            }
            else
                return false;
        }

        // Niet gecached. Ophalen!
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 200);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $httpCode = $info["http_code"];
        \AppRoot::doCliOutput("  * HTTP: ".$httpCode);
        if ($httpCode == 200)
        {
            $xml = new \SimpleXMLElement($result);

            $timeOffset = strtotime("now") - strtotime((string)$xml->currentTime);
            $this->cachedUntil = date("Y-m-d H:i:s", strtotime((string)$xml->cachedUntil) + $timeOffset);

            if (isset($xml->error))
                $this->addError((int)$xml->error["code"], (string)$xml->error);

            // Toevoegen in cache..
            $cacheID = \MySQL::getDB()->insert("api_log", [
                "url"		=> $this->url,
                "execdate"	=> date("Y-m-d H:i:s"),
                "cachedate"	=> $this->cachedUntil
            ]);

            // Schrijf cachefile.
            $cacheFileName = \Tools::checkDirectory($this->cacheDir).$cacheID.".xml";
            \AppRoot::doCliOutput("Write to cache: ".$cacheFileName);
            $cacheFile = fopen($cacheFileName, "w");
            fwrite($cacheFile, $result);
            fclose($cacheFile);

            return $xml;
        }
        else if ($httpCode > 500)
        {
            throw new \Exception("eve api down?\n".$this->url, $httpCode);
        }
        else if ($httpCode == 403)
        {
            // Forbidden. Return authentication failure
            $this->addError(202, "403 forbidden received. API key authentication failure.");
        }
        else
        {
            if ($result && $httpCode != 404)
            {
                $xml = new \SimpleXMLElement($result);
                if (isset($xml->error))
                    $this->addError((int)$xml->error["code"], (string)$xml->error);
            }
            else
            {
                // Andere fout. Server down?
                throw new \Exception("eve api error", $httpCode);
            }
        }

        return false;
    }

    function getCachedUntill()
    {
        return $this->cachedUntil;
    }

    private function addError($code, $message)
    {
        $this->errors[$code] = $message;
        \AppRoot::error("API ERROR [".$code."] ".$message);
    }

    function getErrors()
    {
        if (count($this->errors) > 0)
            return $this->errors;
        else
            return false;
    }

    function getKeysByUserID($userID)
    {
        $keys = array();
        if ($results = \MySQL::getDB()->getRows("SELECT * FROM api_keys WHERE userid = ? AND banned = 0", [$userID])) {
            foreach ($results as $result) {
                $api = new \eve\model\API();
                $api->load($result);
                $keys[] = $api;
            }
        }
        return $keys;
    }

    function validate($apiKeyID)
    {
        $api = new \eve\model\API($apiKeyID);
        $api->validate(true,true);

        $valid = ($api->valid)?1:0;
        $lastcheck = \Tools::getFullDate($api->lastValidateDate)." - ".date("H:i",strtotime($api->lastValidateDate));

        return json_encode(array("valid"=> $valid, "lastcheck" => $lastcheck, "status" => $api->getStatus()));
    }
}