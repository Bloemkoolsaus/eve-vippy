<?php
namespace map\controller;

class Map
{
    /**
     * Get notices
     * @param \scanning\model\Chain $chain
     * @return \notices\model\Notice[]
     */
    function getNotices(\scanning\model\Chain $chain)
    {
        $notices = array();

        // Build query
        $queryParts = [
            "n.deleted = 0",
            "n.expiredate > '".date("Y-m-d H:i:s")."'",
            "n.authgroupid = ".$chain->authgroupID,
            "(w.chainid = ".$chain->id." or n.global > 0)"
        ];

        // Exec query
        if ($results = \MySQL::getDB()->getRows("SELECT	n.*
                                                FROM	notices n
                                                    INNER JOIN mapwormholes w ON w.solarsystemid = n.solarsystemid
                                                    LEFT JOIN notices_read r ON r.noticeid = n.id AND r.userid = ?
                                                WHERE 	(r.userid IS NULL OR n.persistant > 0)
                                                AND		".implode(" AND ", $queryParts)."
                                                GROUP BY n.id"
                                    , [\User::getUSER()->id]))
        {
            foreach ($results as $result)
            {
                $notice = new \notices\model\Notice();
                $notice->load($result);
                $notices[] = $notice;
            }
        }

        return $notices;
    }

    function getWormholes(\map\model\Map $map)
    {
        \AppRoot::debug("getWormholes(".$map->id.")");
        $wormholes = array();
        $characters = $this->getCharacterLocations($map);

        $myCurrentSystems = array();
        foreach ($characters as $systemID => $chars) {
            foreach ($chars as $char) {
                if ($char["isme"] > 0)
                    $myCurrentSystems[] = $systemID;
            }
        }

        if ($results = \MySQL::getDB()->getRows("SELECT wh.id, wh.fullyscanned, s.solarsystemid, r.regionname,
                                                        s.solarsystemname, wh.name AS solarsystemtitle,
                                                        c2.homesystemname, k.name AS knownname,
                                                        IF(k.status IS NOT NULL, k.status, 0) AS known,
                                                        s.security, s.regionid, wh.x, wh.y, wh.status, wh.permanent
                                                FROM 	mapwormholes wh
                                                    INNER JOIN mapwormholechains c1 ON c1.id = wh.chainid
                                                    LEFT JOIN ".\eve\Module::eveDB().".mapsolarsystems s ON s.solarsystemid = wh.solarsystemid
                                                    LEFT JOIN ".\eve\Module::eveDB().".mapregions r ON r.regionid = s.regionid
                                                    LEFT JOIN map_knownwormhole k 	ON k.solarsystemid = s.solarsystemid
                                                                                    AND	k.authgroupid = c1.authgroupid
                                                    LEFT JOIN mapwormholechains c2 	ON c2.deleted = 0
                                                                                    AND c2.authgroupid = c1.authgroupid
                                                                                    AND c2.homesystemid = wh.solarsystemid
                                                WHERE	wh.chainid = ?
                                                GROUP BY wh.id"
                                    , array($map->id)))
        {
            foreach ($results as $result)
            {
                \AppRoot::debug("=============== NEW scanning-system: ".$result["id"]." ==================================");
                $data = array();
                $system = null;
                if ($result["solarsystemid"] != null)
                    $system = new \scanning\model\System($result["solarsystemid"]);

                $data["id"] = $result["id"];
                $data["name"] = htmlentities($result["solarsystemname"]);
                $data["status"] = $result["status"];
                $data["position"]["x"] = $result["x"];
                $data["position"]["y"] = $result["y"];
                $data["persistant"] = ($result["permanent"] > 0) ? true : false;

                if ($result["fullyscanned"] != null && strlen(trim($result["fullyscanned"])) > 0) {
                    if (strtotime($result["fullyscanned"]) > 0) {
                        $age = strtotime("now")-strtotime($result["fullyscanned"]);
                        $data["fullyscanned"] = floor($age/3600);
                    }
                }

                $data["whsystem"]["name"] = htmlentities($result["solarsystemtitle"]);

                if (strlen(trim($result["homesystemname"])) > 0)
                    $data["whsystem"]["homesystem"] = htmlentities($result["homesystemname"]);

                if ($system != null)
                {
                    $data["solarsystem"]["id"] = $result["solarsystemid"];
                    $data["solarsystem"]["name"] = htmlentities($result["solarsystemname"]);
                    $data["solarsystem"]["region"] = htmlentities($result["regionname"]);
                    $data["solarsystem"]["class"]["name"] = ($system->isWSpace())?"WH":$system->getClass(true);
                    $data["solarsystem"]["class"]["color"] = $system->getClassColor();

                    if ($system->isShattered() !== false) {
                        if ($system->isShattered() == "frigate")
                            $data["whsystem"]["titles"][] = array("name" => "Small Ship Shattered", "color" => "#442266");
                        else
                            $data["whsystem"]["titles"][] = array("name" => "Shattered", "color" => "#442266");
                    }
                }
                else
                {
                    // Unmapped system. Toevoegen als title.
                    $data["whsystem"]["titles"][] = array("name" => "Unmapped");
                }

                // Known system. Toevoegen als title.
                if (strlen(trim($result["knownname"])) > 0)
                {
                    $title = array("name" => htmlentities($result["knownname"]));
                    if ($result["known"] < 0)
                        $title["color"] = "#CC0000";
                    if ($result["known"] > 0)
                        $title["color"] = "#0066FF";

                    $data["whsystem"]["titles"][] = $title;
                }

                $names = array();
                if (isset($data["solarsystem"]))
                    $names[] = $data["solarsystem"]["name"];
                $names[] = $data["whsystem"]["name"];
                if (!in_array($result["homesystemname"], $names))
                    $names[] = $result["homesystemname"];

                $data["name"] = array();
                foreach ($names as $name) {
                    if (strlen(trim($name)) > 0)
                        $data["name"][] = $name;
                }
                $data["name"] = implode(" - ",$data["name"]);

                if ($system != null)
                {
                    if ($system->isWSpace())
                    {
                        $data["whsystem"]["class"] = $system->getClass(true);
                        $data["whsystem"]["statics"] = $system->getStatics(true);
                        if ($system->getEffect())
                            $data["whsystem"]["effect"] = $system->getEffect();
                    }
                    else
                    {
                        if ($system->getStationSystem())
                            $data["attributes"]["stations"] = true;
                    }

                    if ($system->isHSIsland())
                        $data["attributes"]["hsisland"] = true;
                    if ($system->isDirectHS())
                        $data["attributes"]["direcths"] = true;
                    if ($system->hasCapsInRange())
                        $data["attributes"]["cyno"] = true;
                    if ($system->isFactionWarfareSystem())
                        $data["attributes"]["fwsystem"] = true;
                    if ($system->isContested())
                        $data["attributes"]["contested"] = true;
                    if ($system->getFactionID())
                        $data["attributes"]["factionid"] = $system->getFactionID();

                    // K-Space? Zoek dichtsbijzijnde tradehub
                    if (!$system->isWSpace())
                    {
                        $closeSysConsole = new \map\console\ClosestSystems();
                        $closestSystems = $closeSysConsole->getClosestSystems($system, true);

                        if (count($closestSystems) > 0) {
                            $data["tradehub"]["name"] = $closestSystems[0]->name;
                            $data["tradehub"]["jumps"] = $closestSystems[0]->nrJumps;
                        }
                    }

                    $data["kills"] = $system->getRecentKills();
                    unset($data["kills"]["date"]);

                    if (isset($characters[$system->id]))
                        $data["characters"] =  $characters[$system->id];

                    if (in_array($system->id, $myCurrentSystems))
                        $data["insystem"] = true;
                }

                \AppRoot::debug("=============== GOT scanning-system: ".$data["id"]." ==================================");
                $wormholes[] = $data;
            }
        }

        return $wormholes;
    }

    function getConnections(\map\model\Map $map)
    {
        $connections = array();
        if ($results = \MySQL::getDB()->getRows("SELECT c.*,
                                                        IF(f.x > t.x, f.x, t.x) as fx,
                                                        IF(f.x > t.x, f.y, t.y) as fy,
                                                        IF(f.x > t.x, t.x, f.x) as tx,
                                                        IF(f.x > t.x, t.y, f.y) as ty
                                                FROM    mapwormholeconnections c
                                                    INNER JOIN mapwormholes f on f.id = c.fromwormholeid AND f.chainid = ?
                                                    INNER JOIN mapwormholes t on t.id = c.towormholeid AND t.chainid = ?
                                                WHERE	c.chainid = ?"
                                     , [$map->id,$map->id,$map->id]))
        {
            foreach ($results as $result)
            {
                $data = array();
                $data["id"] = $result["id"];

                $data["from"]["system"] = $result["fromwormholeid"];
                $data["from"]["whtype"] = $result["fromwhtypeid"];
                $data["from"]["position"]["x"] = $result["fx"];
                $data["from"]["position"]["y"] = $result["fy"];

                $data["to"]["system"] = $result["towormholeid"];
                $data["to"]["whtype"] = $result["towhtypeid"];
                $data["to"]["position"]["x"] = $result["tx"];
                $data["to"]["position"]["y"] = $result["ty"];

                $data["attributes"] = array();
                if ($result["kspacejumps"] != 0)
                    $data["attributes"]["kspacejumps"] = $result["kspacejumps"];
                else if ($result["allowcapitals"] > 0)
                    $data["attributes"]["capital"] = true;
                if ($result["frigatehole"] > 0)
                    $data["attributes"]["frigate"] = true;
                if ($result["eol"] > 0)
                    $data["attributes"]["eol"] = true;
                if ($result["mass"] > 0)
                    $data["attributes"]["mass"] = $result["mass"];
                if ($result["normalgates"] > 0)
                    $data["attributes"]["normalgates"] = true;

                $connections[] = $data;
            }
        }

        return $connections;
    }

    function getCharacterLocations(\map\model\Map $map)
    {
        \AppRoot::debug("getCharacterLocations(".$map->id.")");
        $characters = array();
        if (count(\User::getUSER()->getAuthGroupsIDs()) > 0)
        {
            if ($results = \MySQL::getDB()->getRows("select	c.id, c.name, c.userid, l.solarsystemid
                                                     from   map_character_locations l
                                                        inner join characters c ON c.id = l.characterid
                                                     where  l.authgroupid = ?
                                                     and    l.lastdate >= ?
                                                     order by c.name"
                    , [$map->authgroupID, date("Y-m-d H:i:s", strtotime("now")-(60*5))]))
            {
                foreach ($results as $result)
                {
                    $characters[$result["solarsystemid"]][] = array(
                        "id" 	=> $result["id"],
                        "name" 	=> $result["name"],
                        "isme"	=> (\User::getUSER()->id == $result["userid"])?1:0);
                }
            }
        }

        return $characters;
    }
}