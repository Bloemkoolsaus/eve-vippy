<?php
namespace eve\model;

class SolarSystem
{
    public $id;
    public $name;
    public $constellation;
    public $region;
    public $security;

    private $info = null;
    private $regionObj = null;
    private $faction = null;
    private $knownSystem = null;
    private $jumps = null;
    private $_status = [];
    private $_anomalies;

    function __construct($id=false)
    {
        if ($id) {
            $this->id = $id;
            $this->load();
        }
    }

    function load($result=false)
    {
        if (!$result)
        {
            $cacheFileName = "solarsystem/".$this->id."/solarsystem.json";

            // Eerst in cache kijken
            if ($result = \Cache::file()->get($cacheFileName))
                $result = json_decode($result, true);
            else {
                $result = \MySQL::getDB()->getRow("SELECT * FROM ".\eve\Module::eveDB().".mapsolarsystems WHERE solarsystemid = ?", array($this->id));
                \Cache::file()->set($cacheFileName, json_encode($result));
            }
        }

        if ($result)
        {
            $this->id = $result["solarsystemid"];
            $this->name = $result["solarsystemname"];
            $this->constellation = $result["constellationid"];
            $this->region = $result["regionid"];
            $this->security = $result["security"];
        }
    }

    function getFullname()
    {
        return "<span style='color: ".$this->getClassColor()."'>".$this->getClass(true)."</span> ".$this->name;
    }

    /**
     * Get region
     * @return \eve\model\Region
     */
    function getRegion()
    {
        if ($this->regionObj == null)
            $this->regionObj = new \eve\model\Region($this->region);

        return $this->regionObj;
    }

    function getNotes()
    {
        if (\User::getUSER()) {


            if ($result = \MySQL::getDB()->getRow("select *
                                                   from mapwormholenotes
                                                   where solarsystemid = ?
                                                   and authgroupid IN (".implode(",",\User::getUSER()->getAuthGroupsIDs()).")"
                                            , [$this->id]))
            {
                return $result;
            }
        }

        return null;
    }

    function resetNotes($authGroupID)
    {
        \MySQL::getDB()->delete("mapwormholenotes", ["solarsystemid" => $this->id, "authgroupid" => $authGroupID]);
    }

    function setNotes($notes, $authGroupID)
    {
        // Kijk eerst of de notes wel gezijgd zijn..
        $curNotes = $this->getNotes();
        if (!isset($curNotes) || $curNotes["notes"] != \MySQL::escape($notes))
        {
            \MySQL::getDB()->updateinsert("mapwormholenotes", [
                "solarsystemid"	=> $this->id,
                "notes"			=> $notes,
                "authgroupid"	=> $authGroupID,
                "updatedate"	=> date("Y-m-d H:i:s")
            ], [
                "solarsystemid" => $this->id,
                "authgroupid" => $authGroupID
            ]);
        }
    }

    private function fetchSystemInfo()
    {
        $this->info = array();
        $cacheFileName = "solarsystem/".$this->id."/systeminfo.json";

        if ($cache = \Cache::file()->get($cacheFileName))
        {
            $this->info = json_decode($cache,true);
        }
        else
        {
            $controller = new \eve\controller\SolarSystem();

            $this->info["class"] = $this->getClassInfo(true);
            $this->info["statics"] = $controller->getWormholeStatics($this->id);
            $this->info["effect"] = $controller->getWormholeEffect($this->id);
            $this->info["stations"] = $controller->getStations($this->id);
            $this->info["hsisland"] = false;
            $this->info["direcths"] = false;

            if (!$this->isWSpace()) {
                if ($this->getClass(true) == "HS") {
                    $nrSafeJumpsToJita = $controller->getNrJumps($this->id, 30000142, 0.45);
                    $this->info["hsisland"] = ($nrSafeJumpsToJita === null) ? true : false;
                } else {
                    $nrSafeJumpsToJita = $controller->getNrJumps($this->id, 30000142, 0.45);
                    $this->info["direcths"] = ($nrSafeJumpsToJita === null) ? false : true;
                }
            }

            $sov = $this->getSovStatistics();
            $this->info["fwsystem"] = ($sov["fwsystem"] > 0) ? true : false;
            $this->info["contested"] = ($sov["contested"] > 0) ? true : false;
            $this->info["factionid"] = $sov["factionid"];
            $this->info["allianceid"] = $sov["allianceid"];

            \Cache::file()->set($cacheFileName, json_encode($this->info));
        }
    }

    private function getClassInfo($retry=false)
    {
        $info = ["id" => 0, "tag" => "", "name" => "n/a", "color" => "#000000"];

        $controller = new \eve\controller\Solarsystem();
        $class = $controller->getClassByRegion($this->region);
        if ($class > 0)
            $class = "C".$class;
        else
            $class = $controller->parseSecStatus($this->security);


        // Shattered?
        $info["shattered"] = false;
        if ($result = \MySQL::getDB()->getRow("SELECT * FROM mapwormholes_shattered WHERE constellationid = ?", [$this->constellation])) {
            $info["shattered"] = $result["type"];
        }

        // ClassID ophalen
        if ($result = \MySQL::getDB()->getRow("SELECT * FROM mapsolarsystemclasses WHERE tag = ?", [$class]))
        {
            $info["id"] = $result["id"];
            $info["tag"] = $result["tag"];
            $info["name"] = $result["name"];
            $info["color"] = $result["color"];

            // Toevoegen aan locatie tabel
            \MySQL::getDB()->updateinsert("maplocationwormholeclasses", [
                "locationid" => $this->id,
                "wormholeclassid" => $result["id"]
            ], [
                "locationid" => $this->id
            ]);
        }

        return $info;
    }

    function getClass($short=false)
    {
        if ($this->info == null || !isset($this->info["class"]["tag"]))
            $this->fetchSystemInfo();

        return ($short) ? $this->info["class"]["tag"] : $this->info["class"]["name"];
    }

    function getClassID()
    {
        if ($this->info == null || !isset($this->info["class"]["tag"]))
            $this->fetchSystemInfo();

        return $this->info["class"]["id"];
    }

    function getClassColor()
    {
        if ($this->info == null || !isset($this->info["class"]["color"]))
            $this->fetchSystemInfo();

        return $this->info["class"]["color"];
    }

    /**
     * Get known system
     * @return \map\model\KnownWormhole|null
     */
    function getKnownSystem()
    {
        if ($this->knownSystem === null)
            $this->knownSystem = \map\model\KnownWormhole::findBySolarSystemID($this->id);

        return $this->knownSystem;
    }

    function isKnownSystem()
    {
        if ($this->getKnownSystem() !== null)
            return true;
        else
            return false;
    }

    function getRecentKills()
    {
        $cacheFilename = "solarsystem/".$this->id."/recentkills.json";
        if ($cache = \Cache::file()->get($cacheFilename)) {
            $data = json_decode($cache,true);
            // cache mag niet ouder zijn dan een uur
            if (isset($data["date"])) {
                if (strtotime($data["date"]) > mktime(date("H")-1,date("i"),date("s"),date("m"),date("d"),date("Y")))
                    $kills = $data;
            }
        }
        if (!isset($kills))
            $kills = $this->setRecentKills();

        return $kills;
    }

    function getSovStatistics()
    {
        $cacheFilename = "solarsystem/".$this->id."/sovstatistics.json";
        if ($cache = \Cache::file()->get($cacheFilename))
            $data = json_decode($cache,true);
        else
            $data = $this->setSovStatistics();

        return $data;
    }

    function getFactionID()
    {
        if ($this->info == null || !isset($this->info["factionid"]))
            $this->fetchSystemInfo();
        if ($this->info["factionid"] !== null)
            return $this->info["factionid"];

        return 0;
    }

    function getEffect()
    {
        if ($this->info == null || !isset($this->info["effect"]))
            $this->fetchSystemInfo();

        return $this->info["effect"];
    }

    function getStatics($short=false, $parsed=true)
    {
        if ($this->info == null || !isset($this->info["statics"]))
            $this->fetchSystemInfo();

        $statics = $this->info["statics"];
        if ($parsed) {
            $statics = array();
            foreach ($this->info["statics"] as $type => $static) {
                $statics[] = $type." (to ".(($short)?$static["tag"]:$static["name"]).")";
            }
        }

        return $statics;
    }

    /**
     * Get nr of stations
     * @return integer
     */
    function getNrStations()
    {
        if ($this->info == null || !isset($this->info["stations"]))
            $this->fetchSystemInfo();

        return count($this->info["stations"]);
    }

    /**
     * is station system?
     * @return boolean
     */
    function getStationSystem()
    {
        if ($this->getNrStations() > 0)
            return true;
        else
            return false;
    }

    /**
     * Aantal normale gate-jumps tussen 2 systemen.
     * @param int $systemID
     * @param int $minSecurity minimale sec-status van systemen.
     * @return int
     */
    function getNrJumpsTo($systemID, $minSecurity=null)
    {
        if ($this->jumps === null) {
            $this->jumps = array();
            if ($cache = \Cache::file()->get("solarsystem/".$this->id."/nrjumps.json"))
                $this->jumps = json_decode($cache,true);
        }

        if (!isset($this->jumps[$systemID])) {
            $controller = new \eve\controller\SolarSystem();
            $this->jumps[$systemID] = $controller->getNrJumps($this->id, $systemID, $minSecurity=null);
            \Cache::file()->set("solarsystem/".$this->id."/nrjumps.json", json_encode($this->jumps));
        }

        return $this->jumps[$systemID];
    }

    /**
     * Get solarsystems that are in cyno-jump range
     * @param double $maxJumpRange
     * return multitype:\eve\model\solarsystem
     * @return array
     */
    function getSystemsInJumpRange($maxJumpRange)
    {
        $cacheFileName = "solarsystem/".$this->id."/cynorange.json";

        $results = false;
        if ($cache = \Cache::file()->get($cacheFileName))
            $results = json_decode($cache, true);

        $systems = array();
        if (!$results)
        {
            if ($results = \MySQL::getDB()->getRows("SELECT end.*
                                                    FROM    ".\eve\Module::eveDB().".mapsolarsystems start,
                                                            ".\eve\Module::eveDB().".mapsolarsystems end
                                                    WHERE   start.solarsystemid = ?
                                                    AND     end.security < 0.45
                                                    AND     ((SQRT(
                                                                POWER((start.x-end.x),2) +
                                                                POWER((start.y-end.y),2) +
                                                                POWER((start.z-end.z),2)
                                                            )/149597870691)/63239.6717) < ".$maxJumpRange
                                            , array($this->id)))
            {
                \Cache::file()->set($cacheFileName, json_encode($results));
            }
        }

        foreach ($results as $result)
        {
            $system = new \eve\model\SolarSystem();
            $system->load($result);
            $systems[] = $system;
        }

        return $systems;
    }

    /**
     * Get distance in lightyears
     * @param integer $solarSystemID
     * @return number
     */
    function getLightyearDistance($solarSystemID)
    {
        \AppRoot::debug("getLightyearDistance($solarSystemID)");

        $cacheFileName = "solarsystem/".$this->id."/lightyears.json";
        $data = array();
        if ($cache = \Cache::file()->get($cacheFileName))
        {
            $data = json_decode($cache, true);
            if (isset($data[$solarSystemID]))
                return $data[$solarSystemID];
        }

        if ($this->id !== $solarSystemID)
        {
            if ($result = \MySQL::getDB()->getRow("	SELECT 	((SQRT(	POWER((start.x-end.x),2) +
                                                                    POWER((start.y-end.y),2) +
                                                                    POWER((start.z-end.z),2)
                                                            )/149597870691)/63239.6717) AS distance
                                                    FROM    ".\eve\Module::eveDB().".mapsolarsystems start,
                                                            ".\eve\Module::eveDB().".mapsolarsystems end
                                                    WHERE   start.solarsystemid = ?
                                                    AND   	end.solarsystemid = ?"
                                        , array($this->id, $solarSystemID)))
            {
                $data[$solarSystemID] = $result["distance"];
                \Cache::file()->set($cacheFileName, json_encode($data));
                return $result["distance"];
            }
        }

        return 0;
    }

    /**
     * Is this system in jumprange??
     * @param int $solarSystemID
     * @param double $maxJumpRange
     * @return boolean
     */
    function isSystemInJumpRange($solarSystemID, $maxJumpRange)
    {
        if ($this->getLightyearDistance($solarSystemID) <= $maxJumpRange)
            return true;
        else
            return false;
    }

    function getNrCapitalJumps($solarSystemID, $maxJumpRange)
    {
        if ($solarSystemID == $this->id)
            return 0;

        if ($this->isSystemInJumpRange($solarSystemID, $maxJumpRange))
            return 1;

        return count($this->getCapitalRoute($solarSystemID, $maxJumpRange));
    }

    function getCapitalRoute($solarSystemID, $maxJumpRange)
    {
        \AppRoot::debug("getCapitalRoute($solarSystemID, $maxJumpRange)");

        $cacheFileName = "solarsystem/".$this->id."/capitalroute.json";
        $data = array();
        if ($cache = \Cache::file()->get($cacheFileName))
        {
            $data = json_decode($cache, true);
            if (isset($data[$solarSystemID][$maxJumpRange]))
                return $data[$solarSystemID][$maxJumpRange];
        }

        $route = array();
        $desto = $this;

        $i=0;
        while ($desto->id != $solarSystemID)
        {
            $i++;
            $desto = $desto->getCapitalMidpoint($solarSystemID, $maxJumpRange);
            if ($desto == null)
                break;

            $route[] = $desto;
            if ($i>50)
                break;
        }

        $data[$solarSystemID][$maxJumpRange] = $route;
        \Cache::file()->set($cacheFileName, json_encode($data));
        return $route;
    }

    /**
     * get first capital jump midpoint to a certain system
     * @param integer $solarSystemID
     * @param integer $maxJumpRange
     * @return \eve\model\SolarSystem|null
     */
    function getCapitalMidpoint($solarSystemID, $maxJumpRange)
    {
        $midpoint = null;
        $closestDistance = 0;
        foreach ($this->getSystemsInJumpRange($maxJumpRange) as $system)
        {
            $distance = $system->getLightyearDistance($solarSystemID);
            if ($midpoint === null || $closestDistance > $distance)
            {
                $closestDistance = $distance;
                $midpoint = $system;
            }
        }

        return $midpoint;
    }

    /**
     * Get anomalies
     * @param integer $mapID
     * @return \map\model\Anomaly[]
     */
    function getAnomalies($mapID)
    {
        if ($this->_anomalies === null) {
            $map = new \map\model\Map($mapID);
            $this->_anomalies = \map\model\Anomaly::findAll(["solarsystemid" => $this->id, "authgroupid" => $map->authgroupID]);
        }

        return $this->_anomalies;
    }

    function isHSIsland()
    {
        if ($this->info == null || !isset($this->info["hsisland"]))
            $this->fetchSystemInfo();

        return $this->info["hsisland"];
    }

    function isDirectHS()
    {
        if ($this->info == null || !isset($this->info["direcths"]))
            $this->fetchSystemInfo();

        return $this->info["direcths"];
    }

    function isFactionWarfareSystem()
    {
        if ($this->info == null || !isset($this->info["fwsystem"]))
            $this->fetchSystemInfo();

        return $this->info["fwsystem"];
    }

    function isContested()
    {
        if ($this->info == null || !isset($this->info["contested"]))
            $this->fetchSystemInfo();

        return $this->info["contested"];
    }

    /**
     * Is this system in wormhole-space?
     * @return boolean
     */
    function isWSpace()
    {
        $ontroller = new \eve\controller\SolarSystem();
        if ($ontroller->getClassByRegion($this->region) > 0)
            return true;
        else
            return false;
    }

    /**
     * Is this a shattered system?
     * @return boolean
     */
    function isShattered()
    {
        if ($this->info == null || !isset($this->info["shattered"]))
            $this->fetchSystemInfo();

        return $this->info["class"]["shattered"];
    }

    /**
     * Is this system in known-space?
     * @return boolean
     */
    function isKSpace()
    {
        if (!$this->isWSpace())
            return true;
        else
            return false;
    }

    /**
     * Is cap capable?
     * @return boolean
     */
    function isCapitalCapable()
    {
        switch ($this->getClass(true))
        {
            case "C1":
                return false;
            case "C2":
                return false;
            case "C3":
                return false;
            case "C4":
                return false;
            case "HS":
                return false;
            case "N/A":
                return false;
        }

        return true;
    }

    /**
     * Is system tradehub?
     * @return bool
     */
    function isTradehub()
    {
        foreach (\eve\model\SolarSystem::getTradehubs() as $hub) {
            if ($hub->id == $this->id)
                return true;
        }
        return false;
    }

    /**
     * Has frigate only connections?
     * @return boolean
     */
    function isFrigateOnly()
    {
        if ($this->isShattered() == "frigate")
            return true;

        return false;
    }

    /**
     * Get wormhole types
     * @return array
     */
    function getWormholeTypes()
    {
        $types = [];
        foreach (\scanning\model\WormholeType::findBySystemID($this->id) as $type) {
            $types[] = $type;
        }
        return $types;
    }

    function setRecentKills()
    {
        $date = date("Y-m-d H:i:s", mktime( date("H")-2, date("i"), date("s"), date("m"), date("d"), date("Y")));
        $kills = array("pvp" => 0, "pve" => 0);
        if ($results = \MySQL::getDB()->getRows("SELECT	shipkills+podkills AS shipkills, npckills
                                                FROM	mapsolarsystem_kill_statistics
                                                WHERE	solarsystemid = ?
                                                AND		servertime >= ?
                                                ORDER BY statdate DESC"
                                    , array($this->id, $date)))
        {
            foreach ($results as $result)
            {
                $kills["pvp"] += $result["shipkills"]-0;
                $kills["pve"] += $result["npckills"]-0;
            }
        }

        $kills["date"] = date("Y-m-d H:i:s");
        $cacheFilename = "solarsystem/".$this->id."/recentkills.json";
        \Cache::file()->set($cacheFilename, json_encode($kills));

        return $kills;
    }

    function setSovStatistics($result=false)
    {
        $data = array(	"factionid"		=> 0,
                        "allianceid"	=> 0,
                        "fwsystem"		=> 0,
                        "contested"		=> 0);
        if (!$result)
            $result = \MySQL::getDB()->getRow("SELECT * FROM mapsolarsystem_sov_stats WHERE solarsystemid = ?", array($this->id));

        if ($result)
        {
            $data = array(	"factionid"		=> $result["factionid"],
                            "allianceid"	=> $result["allianceid"],
                            "fwsystem"		=> $result["fwsystem"],
                            "contested"		=> $result["contested"]);

            $cacheFilename = "solarsystem/".$this->id."/sovstatistics.json";
            \Cache::file()->set($cacheFilename, json_encode($data));
        }

        return $data;
    }

    function getActivityStatistics()
    {
        if ($results = \MySQL::getDB()->getRows("	SELECT	statdate, evetime, servertime,
                                                            shipkills+podkills AS shipkills, npckills
                                                    FROM	mapsolarsystem_kill_statistics
                                                    WHERE	solarsystemid = ?
                                                    ORDER BY statdate DESC"
                                        , array($this->id)))
        {
            foreach ($results as $result)
            {
                $datediff = strtotime($result["servertime"])-strtotime($result["evetime"]);
                $statTime = strtotime($result["statdate"])+$datediff;

                $dbStats[date("Y", $statTime)-0][date("m", $statTime)-0][date("d", $statTime)-0][date("H", $statTime)-0] = array(
                                    "statdate"	=> date("Y-m-d H:i:s",strtotime($result["statdate"])),
                                    "offset"	=> date("Y-m-d H:i:s",$statTime),
                                    "shipkills" => $result["shipkills"],
                                    "npckills"	=> $result["npckills"]);
            }
        }

        $i = 0;
        $startDate = date("Y-m-d H:i:s", mktime(date("H")-72, date("i"), date("s"), date("m"), date("d"), date("Y")));
        $currentDate = date("Y-m-d H:i:s");

        while ($currentDate > $startDate)
        {
            $currentDate = strtotime($currentDate);
            $currentDate = $currentDate - (60*60);
            $currentDate = date("Y-m-d H:i:s", $currentDate);
            $i++;

            if ($i > 100)
                break;

            $year = date("Y",strtotime($currentDate))-0;
            $month = date("m",strtotime($currentDate))-0;
            $day = date("d",strtotime($currentDate))-0;
            $hour = date("H",strtotime($currentDate))-0;

            $shipKills = (isset($dbStats[$year][$month][$day][$hour]["shipkills"]))?$dbStats[$year][$month][$day][$hour]["shipkills"]:0;
            $npcKills = (isset($dbStats[$year][$month][$day][$hour]["npckills"]))?$dbStats[$year][$month][$day][$hour]["npckills"]:0;

            $statistics[] = array(	"datetime"	=> $currentDate,
                                    "age"		=> $i,
                                    "shipkills"	=> $shipKills,
                                    "npckills"	=> $npcKills);
        }

        return $statistics;
    }

    function getActivityGraphAge()
    {
        return \AppRoot::getDBConfig("cron_killstats_last");
    }

    function buildActivityGraph()
    {
        // Zoek naar eerder gemaakte grafiek
        $filename = "documents/statistics/killgraphs/".$this->id."/".date("YmdHi").".png";
        if (file_exists($filename))
            return $filename;

        // Goede map aanmaken
        if (!file_exists("documents"))
            mkdir("documents",0777);
        if (!file_exists("documents/statistics"))
            mkdir("documents/statistics",0777);
        if (!file_exists("documents/statistics/killgraphs"))
            mkdir("documents/statistics/killgraphs",0777);
        if (!file_exists("documents/statistics/killgraphs/".$this->id))
            mkdir("documents/statistics/killgraphs/".$this->id,0777);

        // Oude bestanden opruimen
        $directory = "documents/statistics/killgraphs/".$this->id;
        if ($handle = @opendir($directory)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..")
                    @unlink($directory."/".$file);
            }
        }

        // Statistiek ophalen
        $statistics = $this->getActivityStatistics();

        // Start bouwen van grafiek
        $max = 50;
        foreach ($statistics as $stat) {
            while ($max < $stat["npckills"]) {
                $max += 50;
            }
            while ($max < $stat["shipkills"]) {
                $max += 50;
            }
        }

        // Grafiek lijnen (voor anti-alias)
        $imgNPCKills = imagecreatetruecolor(318, 120);

        if (function_exists("imageantialias"))
            imageantialias($imgNPCKills,true);

        $colorNPCKills = imagecolorallocate($imgNPCKills, 20, 170, 80);
        $colorPVPKills = imagecolorallocate($imgNPCKills, 220, 50, 50);
        $black = imagecolorallocate($imgNPCKills, 0, 0, 0);
        imagecolortransparent($imgNPCKills, $black);

        // Grafiek zelf.
        $imgGraph = imagecreatetruecolor(318, 120);
        $white = imagecolorallocate($imgGraph, 120, 120, 120);
        $grey = imagecolorallocate($imgGraph, 50, 50, 50);
        $black = imagecolorallocate($imgGraph, 0, 0, 0);
        imagecolortransparent($imgGraph, $black);

        $style = array($grey,$grey,$grey,IMG_COLOR_TRANSPARENT,IMG_COLOR_TRANSPARENT,IMG_COLOR_TRANSPARENT,IMG_COLOR_TRANSPARENT);
        imagesetstyle($imgGraph, $style);

        imagestring($imgGraph, 1, 35, 10, "NPC Kills", $colorNPCKills);
        imagestring($imgGraph, 1, 35, 20, "PvP Kills", $colorPVPKills);

        $polygonNPC = array();
        $polygonPVP = array();

        $polygonNPC[] = 318;
        $polygonNPC[] = 105;
        $polygonPVP[] = 318;
        $polygonPVP[] = 105;

        foreach ($statistics as $stat)
        {
            $width = (288-(($stat["age"]-1)*4))+30;

            $polygonNPC[] = $width;
            $polygonPVP[] = $width;

            $polygonNPC[] = ($stat["npckills"] > 0) ? 100-(round(($stat["npckills"]/$max)*100))+5 : 105;
            $polygonPVP[] = ($stat["shipkills"] > 0) ? 100-(round(($stat["shipkills"]/$max)*100))+5 : 105;

            if ($stat["age"]%6 == 0 || $stat["age"] == 1)
                imagestring($imgNPCKills, 1, $width-9, 110, $stat["age"]."h", $white);

            if ($stat["age"]%12 == 0 && $stat["age"] < 72)
                imageline($imgGraph, $width, 0, $width, 105, IMG_COLOR_STYLED);
        }

        $polygonNPC[] = 30;
        $polygonNPC[] = 105;
        $polygonPVP[] = 30;
        $polygonPVP[] = 105;

        imagepolygon($imgNPCKills, $polygonPVP, count($polygonPVP)/2, $colorPVPKills);
        imagepolygon($imgNPCKills, $polygonNPC, count($polygonNPC)/2, $colorNPCKills);

        imageline($imgGraph, 30, 5, 318, 5, IMG_COLOR_STYLED);
        imageline($imgGraph, 30, 30, 318, 30, IMG_COLOR_STYLED);
        imageline($imgGraph, 30, 55, 318, 55, IMG_COLOR_STYLED);
        imageline($imgGraph, 30, 80, 318, 80, IMG_COLOR_STYLED);

        imagealphablending($imgGraph,true);
        imagecopymerge($imgGraph, $imgNPCKills, 0, 0, 0, 0, 318, 120, 100);

        imageline($imgGraph, 30, 5, 30, 105, $white);
        imageline($imgGraph, 30, 105, 318, 105, $white);

        $p100 = $max;
        $p75 = round($max/4)*3;
        $p50 = round($max/4)*2;
        $p25 = round($max/4);

        while (strlen($p75) < strlen($p100)) {
            $p75 = " ".$p75;
        }
        while (strlen($p50) < strlen($p100)) {
            $p50 = " ".$p50;
        }
        while (strlen($p25) < strlen($p100)) {
            $p25 = " ".$p25;
        }

        imagestring($imgGraph, 2, 5, 0, $p100, $white);
        imagestring($imgGraph, 2, 5, 25, $p75, $white);
        imagestring($imgGraph, 2, 5, 50, $p50, $white);
        imagestring($imgGraph, 2, 5, 75, $p25, $white);

        imagepng($imgGraph,$filename);
        imagedestroy($imgNPCKills);
        imagedestroy($imgGraph);

        return $filename;
    }



    /**
     * Find system by name
     * @param string $name
     * @return \eve\model\SolarSystem|null
     */
    public static function findByName($name)
    {
        return self::getSolarsystemByName($name);
    }

    /**
     * Get solarsystem by name
     * @param string $name
     * @return \eve\model\SolarSystem|null
     */
    public static function getSolarsystemByName($name)
    {
        if ($result = \MySQL::getDB()->getRow("SELECT * FROM ".\eve\Module::eveDB().".mapsolarsystems WHERE solarsystemname = ?", array($name)))
        {
            $system = new \eve\model\SolarSystem();
            $system->load($result);
            return $system;
        }

        return null;
    }

    /**
     * Get trade hub systems
     * @return \eve\model\SolarSystem[]
     */
    public static function getTradehubs()
    {
        $systems = [];
        if ($results = \MySQL::getDB()->getRows("select s.*
                                                from    ".\eve\Module::eveDB().".mapsolarsystems s
                                                    inner join maptradehubs t on t.solarsystemid = s.solarsystemid
                                                order by s.solarsystemname"))
        {
            foreach ($results as $result)
            {
                $system = new \eve\model\SolarSystem();
                $system->load($result);
                $systems[] = $system;
            }
        }

        return $systems;
    }
}