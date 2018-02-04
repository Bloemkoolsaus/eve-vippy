<?php
namespace esi\model;

class Character extends \eve\model\Character
{
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
            $fleet = \fleets\model\Member::findOne(["characterid" => $this->id]);
            if ($fleet) {
                // Alleen indien in fleet is. Anders is shiptype niet bekend!
                $current = $this->getLocation();
                if ($current)
                    $shiptypeID = $current->shiptypeID;
            }
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

    function setOffline()
    {
        \MySQL::getDB()->update("map_character_locations", ["online" => 0], ["characterid" => $this->id]);
    }


    /**
     * Find character by ID
     * @param $characterID
     * @return \esi\model\Character|null
     */
    public static function findByID($characterID)
    {
        if ($result = \MySQL::getDB()->getRow("select * from characters where id = ?", [$characterID])) {
            $char = new \esi\model\Character($characterID);
            $char->load($result);
            return $char;
        }
        return null;
    }

    /**
     * Find characters with a valid token by user
     * @param $userID
     * @return \esi\model\Character[]
     */
    public static function findByUser($userID)
    {
        $characters = [];
        if ($results = \MySQL::getDB()->getRows("select c.* 
                                                from    characters c
                                                    inner join sso_token t on t.tokenid = c.id and t.tokentype = 'character'
                                                where   c.userid = ?"
                                        , [$userID]))
        {
            foreach ($results as $result) {
                $char = new \esi\model\Character();
                $char->load($result);
                $characters[] = $char;
            }
        }
        return $characters;
    }
}