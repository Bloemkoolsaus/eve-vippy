<?php
namespace crest\model;

class Character extends \eve\model\Character
{
    protected $_token = null;


    function importData()
    {
        try {
            /** Gebruikt reguliere xml api. Scheelt weer crest-requests, rate-limits enzo */
            $charController = new \eve\controller\Character();
            $character = $charController->importCharacter($this->id);

            // Check corp
            $corpController = new \eve\controller\Corporation();
            $corpController->importCorporation($character->corporationID);
        }
        catch (\Exception $e)
        {
            echo "An error occured while importing data from the xml api..<br />".$e->getMessage();
        }
    }

    /**
     * Get CREST Token
     * @return \crest\model\Token|null
     */
    function getToken()
    {
        if ($this->_token === null)
            $this->_token = \crest\model\Token::findOne(["tokentype" => "character", "tokenid" => $this->id]);

        return $this->_token;
    }

    /**
     * Get location solarsystemid
     * @return \stdClass|null
     */
    function getLocation()
    {
        $authgroup = null;
        $location = null;
        foreach ($this->getAuthGroups(false) as $group) {
            $authgroup = $group;
            break;
        }
        if (!$authgroup)
            return null;

        $expireSeconds = 60*5;

        $cache = \Cache::file()->get("locations/".$authgroup->id."/".$this->id);
        if ($cache) {
            $data = json_decode($cache);
            \AppRoot::debug($data);
            if ($data->lastdate > strtotime("now")-$expireSeconds) {
                $location = new \stdClass();
                $location->solarsystemID = (int)$data->solarsystemID;
                $location->shiptypeID = (int)$data->shiptypeID;
                $location->lastdate = (int)$data->lastdate;
            }
        } else {
            if (!$location) {
                if ($result = \MySQL::getDB()->getRow("select solarsystemid, shiptypeid, lastdate
                                                       from   map_character_locations 
                                                       where  characterid = ? 
                                                       and    lastdate > ?"
                                    , [$this->id, date("Y-m-d H:i:s", strtotime("now")-$expireSeconds)]))
                {
                    $location = new \stdClass();
                    $location->solarsystemID = (int)$result["solarsystemid"];
                    $location->shiptypeID = (int)$result["shiptypeid"];
                    $location->lastdate = strtotime($result["lastdate"]);
                }
            }
        }

        return $location;
    }

    /**
     * Set location solarsystemid
     * @param int $solarsystemID
     * @param int $shiptypeID
     * @param bool $online
     */
    function setLocation($solarsystemID=null, $shiptypeID=null, $online=true)
    {
        if (!$shiptypeID) {
            $current = $this->getLocation();
            if ($current)
                $shiptypeID = $current->shiptypeID;
        }

        $location = new \stdClass();
        $location->characterID = $this->id;
        $location->characterName = $this->name;
        $location->userID = $this->userID;
        $location->solarsystemID = ($solarsystemID)?(int)$solarsystemID:0;
        if ($location->solarsystemID) {
            $system = new \eve\model\SolarSystem($location->solarsystemID);
            $location->solarsystemName = $system->name;
        }
        $location->shiptypeID = ($shiptypeID)?(int)$shiptypeID:0;
        if ($location->shiptypeID) {
            $ship = new \eve\model\Ship($location->shiptypeID);
            $location->shiptypeName = $ship->name;
        }
        $location->lastdate = strtotime("now");

        if ($location->solarsystemID) {
            foreach ($this->getAuthGroups(false) as $group) {
                \Cache::file()->set("locations/".$group->id."/".$this->id, $location);
            }
        } else {
            foreach ($this->getAuthGroups(false) as $group) {
                \Cache::file()->remove("locations/".$group->id."/".$this->id);
            }
        }

        \MySQL::getDB()->doQuery("insert into map_character_locations (characterid, solarsystemid, shiptypeid, online, lastdate)
                                  values (".$this->id.", ".$location->solarsystemID.", ".$location->shiptypeID.", ".(($online)?1:0).", '".date("Y-m-d H:i:s")."')
                                  on duplicate key update
                                        solarsystemid = ".$location->solarsystemID.",
                                        shiptypeid = ".$location->shiptypeID.",
                                        online = ".(($online)?1:0).",
                                        lastdate = '".date("Y-m-d H:i:s")."'");
    }


    /**
     * Find character by ID
     * @param $characterID
     * @return \crest\model\Character|null
     */
    public static function findByID($characterID)
    {
        if ($result = \MySQL::getDB()->getRow("select * from characters where id = ?", [$characterID])) {
            $char = new \crest\model\Character($characterID);
            $char->load($result);
            return $char;
        }
        return null;
    }

    /**
     * Find characters with a valid token by user
     * @param $userID
     * @return \crest\model\Character[]
     */
    public static function findByUser($userID)
    {
        $characters = [];
        if ($results = \MySQL::getDB()->getRows("select c.* 
                                                from    characters c
                                                    inner join crest_token t on t.tokenid = c.id and t.tokentype = 'character'
                                                where   c.userid = ?"
                                        , [$userID]))
        {
            foreach ($results as $result) {
                $char = new \crest\model\Character();
                $char->load($result);
                $characters[] = $char;
            }
        }
        return $characters;
    }
}