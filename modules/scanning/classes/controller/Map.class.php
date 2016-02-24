<?php
namespace scanning\controller
{
	class Map
	{
		function fetchSigmap($chainID)
		{
			\AppRoot::debug("----- fetchSigmap(".$chainID.") -----");
			$checkCache = true;
			if (\Tools::REQUEST("nocache"))
				$checkCache = false;

			$currentDate = date("Y-m-d H:i:s");
			$chain = new \scanning\model\Chain($chainID);

			// Kijk of er iets veranderd is in de chain sinds de laatste check.
			//		Zo niet, is natuurlijk geen update nodig.
			if ($checkCache)
			{
				$cacheDate = (isset($_SESSION["vippy_cachedate_map"])) ? $_SESSION["vippy_cachedate_map"] : 0;
				if ($results = \MySQL::getDB()->getRows("SELECT	lastmapupdatedate AS lastdate
														FROM	mapwormholechains
														WHERE	id = ?"
											, array($chain->id)))
				{
					foreach ($results as $result)
					{
						\AppRoot::debug("cache-date: ".date("Y-m-d H:i:s", strtotime($cacheDate)));
						\AppRoot::debug("lastupdate: ".date("Y-m-d H:i:s", strtotime($result["lastdate"])));
						if (strtotime($cacheDate)+60 > strtotime("now"))
						{
							if (strtotime($result["lastdate"]) < strtotime($cacheDate)-2)
							{
								\AppRoot::debug("do cache");
								return "cached";
							}
							else
							{
								\AppRoot::debug("cache out-dated.. gogogo!");
								break;
							}
						}
						else
						{
							\AppRoot::debug("Cache is older then 1 minute");
							break;
						}
					}
				}
			}

			// Maak de map
			$map = array(	"wormholes" 	=> $this->getWormholes($chain),
							"connections" 	=> $this->getConnections($chain),
							"homesystem" 	=> $chain->homesystemID,
							"notices"		=> $this->getNotices($chain));

			// Cache datum opslaan.
			$_SESSION["vippy_cachedate_map"] = $currentDate;

			// Geef de map terug
			return json_encode($map);
		}

		function getNotices(\scanning\model\Chain $chain)
		{
			$notices = array();

			// Build query
			$queryParts = array();
			$queryParts[] = "n.deleted = 0";
			$queryParts[] = "n.expiredate >= NOW()";
			$queryParts[] = "n.authgroupid = ".$chain->authgroupID;
			$queryParts[] = "(w.chainid = ".$chain->id." OR n.global > 0)";

			// Exec query
			if ($results = \MySQL::getDB()->getRows("	SELECT	n.*
														FROM	notices n
															INNER JOIN mapwormholes w ON w.solarsystemid = n.solarsystemid
															LEFT JOIN notices_read r ON r.noticeid = n.id AND r.userid = 1000210
														WHERE 	(r.userid IS NULL OR n.persistant > 0)
														AND		".implode(" AND ", $queryParts)."
														GROUP BY n.id"))
			{
				foreach ($results as $result)
				{
					$notice = new \notices\model\Notice();
					$notice->load($result);

					$notices[] = array(	"id"	=> $notice->id,
										"type" 	=> $notice->getTypeName(),
										"title"	=> $notice->getTitle(),
										"body" 	=> $notice->body,
										"date" 	=> $notice->messageDate,
										"persistant" => ($notice->persistant)?1:0);
				}
			}

			return $notices;
		}

		function fetchSigList($chainID, $systemID)
		{
			$chain = new \scanning\model\Chain($chainID);
			$_SESSION["hidesignatures"] = false;
			$checkCache = true;
			if (\Tools::REQUEST("nocache"))
				$checkCache = false;

			// Markeer volledig gescand
			if (\Tools::REQUEST("fullyscanned"))
			{
				$checkCache = false;
				$wormhole = \scanning\model\Wormhole::getWormholeBySystemID($systemID, $chain->id);
				$wormhole->markFullyScanned();
			}

			// Kijk of er iets veranderd is in de chain sinds de laatste check.
			//		Zo niet, is natuurlijk geen update nodig.
			if ($checkCache)
			{
				$cacheDate = (isset($_SESSION["vippy_cachedate_sigs"])) ? $_SESSION["vippy_cachedate_sigs"] : date("Y-m-d H:i:s");
				if ($result = \MySQL::getDB()->getRow("	SELECT	MAX(s.updatedate) AS lastdate
														FROM	mapsignatures s
															INNER JOIN mapwormholechains c ON c.authgroupid = s.authgroupid
														WHERE	c.id = ?"
											, array($chain->id)))
				{
					\AppRoot::debug("cache-date: ".date("Y-m-d H:i:s",strtotime($cacheDate)));
					\AppRoot::debug("lastupdate: ".date("Y-m-d H:i:s",strtotime($result["lastdate"])));
					if (strtotime($cacheDate) > strtotime($result["lastdate"]))
					{
						if (strtotime($cacheDate) > mktime(date("H"),date("i")-1,date("s"),date("m"),date("d"),date("Y")))
							return "cached";
					}
				}
			}

			$sortBy = (\Tools::REQUEST("sortby")) ? \Tools::REQUEST("sortby") : "sigid";
			$sortDir = (\Tools::REQUEST("sortdir")) ? strtoupper(\Tools::REQUEST("sortdir")) : "ASC";
			$system = new \scanning\model\System($systemID);
			$signatures = array();

			// Signatures halen
			if ($results = \MySQL::getDB()->getRows("SELECT sig.*,
															IF(sig.sigtype='wh',whtype.id,0) as whtypeid,
															IF(sig.sigtype='wh',whtype.name,0) as whtypename,
															IF(sig.sigtype='wh' and class.id > 0,class.tag,'') as whtypeto,
															whtype.lifetime, whtype.jumpmass, whtype.maxmass,
															u1.displayname AS scannedby, u2.displayname AS lastupdateby
													FROM    mapsignatures sig
														INNER JOIN users u1 ON sig.scannedby = u1.id
														INNER JOIN users u2 ON sig.updateby = u2.id
														LEFT JOIN mapwormholetypes whtype ON whtype.id = sig.typeid
														LEFT JOIN mapsolarsystemclasses class ON class.id = whtype.destination
													WHERE   sig.solarsystemid = ?
													AND		sig.deleted = 0
													AND		sig.authgroupid = ?
													ORDER BY ".\MySQL::escape($sortBy)." ".\MySQL::escape($sortDir)
										, array($system->id, $chain->authgroupID)))
			{
				foreach ($results as $data)
				{
					$data["createdate"] = \Tools::getAge($data["scandate"]);
					$data["updatedate"] = \Tools::getAge($data["updatedate"]);
					$signatures[] = $data;
				}
			}

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("sortby", $sortBy);
			$tpl->assign("sortdir", $sortDir);
			$tpl->assign("system", $system);
			$tpl->assign("wormhole", \scanning\model\Wormhole::getWormholeBySystemID($systemID, $chain->id));
			$tpl->assign("signatures", $signatures);

			// Cache datum opslaan.
			$_SESSION["vippy_cachedate_sigs"] = date("Y-m-d H:i:s");

			// Geef de lijst terug.
			return $tpl->fetch("scanning/system/signatures");
		}

		function getWormholes(\scanning\model\Chain $chain)
		{
			\AppRoot::debug("getWormholes(".$chain->id.")");
			$wormholes = array();
			$characters = $this->getCharacterLocations($chain);

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
													    LEFT JOIN mapknownwormholes k 	ON k.solarsystemid = s.solarsystemid
																						AND	k.authgroupid = c1.authgroupid
													    LEFT JOIN mapwormholechains c2 	ON c2.deleted = 0
													                                    AND c2.authgroupid = c1.authgroupid
													    								AND c2.homesystemid = wh.solarsystemid
													WHERE	wh.chainid = ?
													GROUP BY wh.id"
											, array($chain->id)))
			{
				foreach ($results as $result)
				{
					\AppRoot::debug("=============== NEW scanning-system: ".$result["id"]." ==================================");
					$data = array();
					$system = null;
					if ($result["solarsystemid"] != null)
						$system = new \scanning\model\System($result["solarsystemid"]);

					$data["id"] = $result["id"];
					$data["name"] = $result["solarsystemname"];
					$data["status"] = $result["status"];
					$data["position"]["x"] = $result["x"];
					$data["position"]["y"] = $result["y"];
                    $data["persistant"] = ($result["permanent"] > 0) ? true : false;

					if ($result["fullyscanned"] != null && strlen(trim($result["fullyscanned"])) > 0)
					{
						if (strtotime($result["fullyscanned"]) > 0)
						{
							$age = strtotime("now")-strtotime($result["fullyscanned"]);
							$data["fullyscanned"] = floor($age/3600);
						}
					}

					$data["whsystem"]["name"] = $result["solarsystemtitle"];

					if (strlen(trim($result["homesystemname"])) > 0)
						$data["whsystem"]["homesystem"] = $result["homesystemname"];

					if ($system != null)
					{
						$data["solarsystem"]["id"] = $result["solarsystemid"];
						$data["solarsystem"]["name"] = $result["solarsystemname"];
						$data["solarsystem"]["region"] = $result["regionname"];
						$data["solarsystem"]["class"]["name"] = ($system->isWSpace())?"WH":$system->getClass(true);
						$data["solarsystem"]["class"]["color"] = $system->getClassColor();

						if ($system->isShattered() !== false)
						{
							if ($system->isShattered() == "frigate")
								$data["whsystem"]["titles"][] = array("name" => "Small Ship Shattered", "color" => "#442266");
							else
								$data["whsystem"]["titles"][] = array("name" => "Shattered", "color" => "#442266");
						}

						// Kspace. Region toevoegen als title
						if (!$system->isWSpace())
							$data["whsystem"]["titles"][] = array("name" => $data["solarsystem"]["region"]);
					}
					else
					{
						// Unmapped system. Toevoegen als title.
						$data["whsystem"]["titles"][] = array("name" => "Unmapped");
					}

					// Known system. Toevoegen als title.
					if (strlen(trim($result["knownname"])) > 0)
					{
						$title = array("name" => $result["knownname"]);
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

		function getConnections(\scanning\model\Chain $chain)
		{
			$connections = array();
			if ($results = \MySQL::getDB()->getRows("SELECT c.*,
													        IF(f.x > t.x, f.x, t.x) as fx,
													        IF(f.x > t.x, f.y, t.y) as fy,
													        IF(f.x > t.x, t.x, f.x) as tx,
													        IF(f.x > t.x, t.y, f.y) as ty
													FROM    mapwormholeconnections c
														INNER JOIN mapwormholes f on f.id = c.fromwormholeid AND f.chainid = ".\User::getSelectedChain()."
														INNER JOIN mapwormholes t on t.id = c.towormholeid AND t.chainid = ".\User::getSelectedChain()."
													WHERE	c.chainid = ?"
											, array($chain->id)))
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

		function getCharacterLocations(\scanning\model\Chain $chain)
		{
			\AppRoot::debug("getCharacterLocations(".$chain->id.")");
			$characters = array();
			if (count(\User::getUSER()->getAuthGroupsIDs()) > 0)
			{
				if ($results = \MySQL::getDB()->getRows("SELECT	c.id, c.name, c.userid, l.solarsystemid
														FROM	mapwormholecharacterlocations l
														    INNER JOIN characters c ON c.id = l.characterid
														WHERE   l.authgroupid = ?
														AND		l.lastdate >= ?
														ORDER BY c.name"
								, array($chain->authgroupID, date("Y-m-d H:i:s", strtotime("now")-(60*5)))))
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

		function getLegend()
		{
			$tpl = \SmartyTools::getSmarty();
			return $tpl->fetch("scanning/legend");
		}

		function getAddKnownWormholeForm()
		{
			$system = new \scanning\model\System(\Tools::REQUEST("system"));

			$control = new \admin\controller\KnownWormhole();
			$editForm = $control->getEditForm($system->id, "index.php?module=scanning");

			return "<div style='padding:10px;'><h2>Add ".$system->name." to known systems</h2><div>".$editForm."</div></div>";
		}

		function removeFromKnownWormholeForm()
		{
			$system = new \scanning\model\System(\Tools::REQUEST("system"));
			$editForm = "";

			if (\Tools::REQUEST("confirm"))
			{
				if ($system->getKnownSystem() != null)
					$system->getKnownSystem()->delete();

				\AppRoot::redirect("index.php?module=scanning");
			}

			if ($system->getKnownSystem() !== null)
			{
				$link = "index.php?module=scanning&section=map&action=addtoknownsystems&ajax=remove&system=".$system->id."&confirm=1";
				$editForm = "<div style='margin: 15px;'><img src='images/default/alert.png'> Do you want to forget `".$system->name."` as `<span style='color:".$system->getKnownSystem()->getColor()."'>".$system->getKnownSystem()->name."</span>`?</div>";
				$editForm .= "<div><button type='button' onclick='document.location=\"".$link."\"'>Confirm</button></div>";
			}
			else
			{
				$editForm = $system->name." is not a known system.";
			}


			return "<div style='padding:10px;'><h2>Remove ".$system->name." from known systems</h2><div>".$editForm."</div></div>";
		}

		function getActivePilots()
		{
			// Haal alle systemen uit alle andere chains. Deze mogen dus NIET weergegeven worden straks.
			$nsystems = array();
			if ($results = \MySQL::getDB()->getRows("SELECT	solarsystemid
													FROM 	mapwormholes
													WHERE	chainid NOT IN (".implode(",",\User::getUSER()->getAvailibleChainIDs()).")
													GROUP BY solarsystemid"))
			{
				foreach ($results as $result) {
					if (strlen(trim($result["solarsystemid"])) > 0 && $result["solarsystemid"] != 0)
						$nsystems[] = $result["solarsystemid"];
				}
			}

			// Haal alle poppetjes
			$characters = array();
			if (count(\User::getUSER()->getAuthGroupsIDs()) > 0)
			{
				if ($results = \MySQL::getDB()->getRows("SELECT	cl.characterid, cl.solarsystemid, s.solarsystemname, c.name, corp.ticker
														FROM	mapwormholecharacterlocations cl
															INNER JOIN ".\eve\Module::eveDB().".mapsolarsystems s ON s.solarsystemid = cl.solarsystemid
															INNER JOIN characters c ON c.id = cl.characterid
															LEFT JOIN corporations corp ON corp.id = c.corpid
														WHERE	cl.solarsystemid NOT IN (".implode(",",$nsystems).")
														AND		lastdate BETWEEN DATE_ADD(now(), INTERVAL -5 MINUTE) AND NOW()
														AND		c.userid IN (
																	SELECT	u.id
																	FROM	users u
																		INNER JOIN characters c ON c.userid = u.id
																	    INNER JOIN corporations corp ON corp.id = c.corpid
																	    LEFT JOIN user_auth_groups_corporations uc ON uc.corporationid = corp.id
																	    LEFT JOIN user_auth_groups_alliances ua ON ua.allianceid = corp.allianceid
																	WHERE	(uc.authgroupid IN (".implode(",",\User::getUSER()->getAuthGroupsIDs()).")
																	OR		ua.authgroupid IN (".implode(",",\User::getUSER()->getAuthGroupsIDs())."))
																	GROUP BY u.id)
														GROUP BY c.id
														ORDER BY s.solarsystemname, corp.ticker, c.name"))
				{
					foreach ($results as $result)
					{
						$characters[] = array("system"	=> $result["solarsystemname"],
											"ticker"	=> $result["ticker"],
											"pilot"		=> $result["name"]);
					}
				}
			}

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("characters",$characters);
			return $tpl->fetch(\SmartyTools::getTemplateDir("scanning")."activepilots.html");
		}

		function snapToGrid($chain=false)
		{
			if (!$chain)
				$chain = \User::getSelectedChain();

			$rows = array();
			$columns = array();

			\MySQL::getDB()->doQuery("UPDATE mapwormholes SET x = ? WHERE chainid = ? AND x < 0", array(\scanning\Wormhole::$defaultOffset, $chain));
			\MySQL::getDB()->doQuery("UPDATE mapwormholes SET y = ? WHERE chainid = ? AND y < 0", array(\scanning\Wormhole::$defaultOffset, $chain));

			// Rijen uitrekenen
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM mapwormholes WHERE chainid = ? ORDER BY y ASC, x ASC", array($chain)))
			{
				foreach ($results as $key => $result)
				{
					$i = 0;
					$min = 0;
					$max = \scanning\Wormhole::$defaultHeight;
					$fit = false;
					while (!$fit)
					{
						if (!isset($rows[$i]))
							$rows[$i] = array();

						if ($result["y"] >= $min && $result["y"] < $max)
						{
							$rows[$i][] = $result["id"];
							$fit = true;
						}

						$min = $max;
						$max += \scanning\Wormhole::$defaultHeight;
						$i++;
					}
				}
			}

			// Kolommen uitrekenen
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM mapwormholes WHERE chainid = ? ORDER BY x ASC, y ASC", array($chain)))
			{
				foreach ($results as $result)
				{
					$i = 0;
					$min = 0;
					$max = \scanning\Wormhole::$defaultWidth;
					$fit = false;
					while (!$fit)
					{
						if (!isset($columns[$i]))
							$columns[$i] = array();

						if ($result["x"] >= $min && $result["x"] < $max)
						{
							$columns[$i][] = $result["id"];
							$fit = true;
						}

						$min = $max;
						$max += \scanning\Wormhole::$defaultWidth;
						$i++;
					}
				}
			}

			// Coordinaten goed zetten
			$i = 0;
			foreach ($rows as $key => $systems)
			{
				if (count($systems) == 0)
					continue;

				$newY = 50 + ((\scanning\Wormhole::$defaultOffset+\scanning\Wormhole::$defaultHeight)*$i);
				\MySQL::getDB()->doQuery("UPDATE mapwormholes SET y = ".$newY.", updatedate = '".date("Y-m-d H:i:s")."' WHERE id IN (".implode(",",$systems).")");
				$i++;
			}
			$i = 0;
			foreach ($columns as $key => $systems)
			{
				if (count($systems) == 0)
					continue;

				$newX = 50 + ((\scanning\Wormhole::$defaultOffset+\scanning\Wormhole::$defaultWidth)*$i);
				\MySQL::getDB()->doQuery("UPDATE mapwormholes SET x = ".$newX.", updatedate = '".date("Y-m-d H:i:s")."' WHERE id IN (".implode(",",$systems).")");
				$i++;
			}
		}
	}
}
?>