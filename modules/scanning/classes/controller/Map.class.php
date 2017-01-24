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
														FROM	map_signature s
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
													FROM    map_signature sig
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
            return "null";
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
														FROM	map_character_locations cl
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
			$tpl->assign("characters", $characters);
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