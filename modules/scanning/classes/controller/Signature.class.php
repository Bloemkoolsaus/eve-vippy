<?php
namespace scanning\controller
{
	class Signature
	{
		function getOverview()
		{
			// Wijzig locatie
			if (\Tools::REQUEST("changetocurloc") && \eve\model\IGB::getIGB()->isIGB())
			{
				\User::setSelectedSystem(\eve\model\IGB::getIGB()->getSolarsystemID());
				\AppRoot::refresh();
			}

			$chain = \scanning\model\Chain::getCurrentChain();
			$system = new \scanning\model\System(\User::getSelectedSystem());

			// Set notes
			if (\Tools::POST("notes"))
				$system->setNotes(\Tools::POST("notes"));

			// Rename wormhole
			if (\Tools::POST("renameid"))
			{
				$wormhole = new \scanning\Wormhole(\scanning\Wormhole::getWormholeIdBySystem(\Tools::POST("renameid"), $chain->id));
				$wormhole->name = \Tools::POST("renamename");
				$wormhole->status = \Tools::POST("whstatus");
				$wormhole->store();
				\AppRoot::refresh();
			}

			// Check toon. Maak nieuwe aan als deze nog niet bekend is.
			if (\eve\model\IGB::getIGB()->isIGB() && \eve\model\IGB::getIGB()->isTrusted())
			{
				$character = new \eve\model\Character(\eve\model\IGB::getIGB()->getPilotID());
				$character->id = \eve\model\IGB::getIGB()->getPilotID();
				$character->name = \eve\model\IGB::getIGB()->getPilotName();
				$character->corporationID = \eve\model\IGB::getIGB()->getCorporationID();
				$character->userID = \User::getUSER()->id;
				$character->store();

				$corp = \eve\model\Corporation::getCorporationByID(\eve\model\IGB::getIGB()->getCorporationID());
				if ($corp === null)
				{
					$corp = new \eve\model\Corporation(\eve\model\IGB::getIGB()->getCorporationID());
					$corp->id = \eve\model\IGB::getIGB()->getCorporationID();
					$corp->name = \eve\model\IGB::getIGB()->getCorporationName();
					$corp->allianceID = \eve\model\IGB::getIGB()->getAllianceID();
					$corp->store();

					$alliance = new \eve\model\Alliance(\eve\model\IGB::getIGB()->getAllianceID());
					$alliance->id = \eve\model\IGB::getIGB()->getAllianceID();
					$alliance->name = \eve\model\IGB::getIGB()->getAllianceName();
					$alliance->store();
				}
			}


			$wormhole = \scanning\model\Wormhole::getWormholeBySystemID($system->id, $chain->id);


			// Check map grootte.
			// Haal de kleinste left positie
			if ($result = \MySQL::getDB()->getRow("	SELECT  min(x) as x, min(y) as y
													FROM    mapwormholes
													WHERE   chainid = ?"
										, array($chain->id)))
			{
				if ($result["x"] > 50)
				{
					$moveXby = (int)$result["x"]-50;
					\MySQL::getDB()->doQuery("UPDATE mapwormholes SET x = x - ".$moveXby." WHERE chainid = ?", array($chain->id));
				}
				if ($result["y"] > 50)
				{
					$moveYby = (int)$result["y"]-50;
					\MySQL::getDB()->doQuery("UPDATE mapwormholes SET y = y - ".$moveYby." WHERE chainid = ?", array($chain->id));
				}
			}



			// Haal info over dit gat
			$systemTitle = "";
			if ($result = \MySQL::getDB()->getRow("	SELECT	*
													FROM	mapwormholes
													WHERE	solarsystemid = ?
													AND		chainid = ?"
										, array($system->id, $chain->id)))
			{
				$systemTitle = $result["name"];
			}

			$tpl = \SmartyTools::getSmarty();

			$tpl->assign("chain", $chain);
			$tpl->assign("system", $system);
			$tpl->assign("wormhole", $wormhole);
			$tpl->assign("systemTitle", $systemTitle);

			if (isset($_SESSION["trackingonly"]) && $_SESSION["trackingonly"] === true)
				$tpl->assign("trackingonlymode", 1);

			if (!isset($_SESSION["hidesignatures"]) || $_SESSION["hidesignatures"] === true)
				$tpl->assign("hidesignatures", 1);


			if ($notes = $system->getNotes())
			{
				$tpl->assign("notes", $notes["notes"]);
				$tpl->assign("noteslastdate", \Tools::getFullDate($notes["updatedate"]));
			}

			if (!isset($_SESSION["mapzoom"]))
				$_SESSION["mapzoom"] = 100;

			if (\Tools::POST("mapZoom"))
				$_SESSION["mapzoom"] = \Tools::POST("mapZoom");

			if ($_SESSION["mapzoom"] < 10)
				$_SESSION["mapzoom"] = 10;

			$tpl->assign("mapZoom", $_SESSION["mapzoom"]);

			if (\eve\model\IGB::getIGB()->isIGB()) {
				$tpl->assign("igb", \eve\model\IGB::getIGB());
			}

			return $tpl->fetch("scanning/overview");
		}

		/**
		 * Add a signature from the query string
		 * @return Boolean
		 */
		function addSignature()
		{
			$id = \Tools::REQUEST("id");
			$sig = \Tools::REQUEST("sig");
			$type = \Tools::REQUEST("type");
			$info = \Tools::REQUEST("info");
			$typeid = \Tools::REQUEST("typeid");
			$sigStrength = \Tools::REQUEST("signalstrength");

            if (!is_numeric($typeid)) {
                $whtype = \scanning\model\WormholeType::findByName($typeid);
                $typeid = ($whtype != 0) ? $whtype->id : 0;
            }

			if ($id) {
				$signature =  new \scanning\model\Signature($id);
			} else {
				$signature = $this->getSignatureBySigID($sig);
				if ($signature == null)
					$signature =  new \scanning\model\Signature();
			}

			$signature->solarSystemID = \User::getSelectedSystem();
			$signature->sigID = ($sig)?$sig:$signature->sigID;
			$signature->sigType = ($type)?$type:$signature->sigType;
			$signature->sigInfo = ($info)?$info:$signature->sigInfo;
			$signature->sigTypeID = ($typeid)?$typeid:$signature->sigTypeID;
			$signature->signalStrength = ($sigStrength)?$sigStrength:$signature->signalStrength;
			$signature->deleted = false;
			$signature->store();

			return true;
		}

        /**
         * Get a signature by it's signature-id
         * @param integer $sigID
         * @param bool|int $systemID of false for current selected system.
         * @return \scanning\model\Signature
         */
		function getSignatureBySigID($sigID, $systemID=false)
		{
			$chain = new \scanning\model\Chain(\User::getSelectedChain());
			if (!$systemID)
				$systemID = \User::getSelectedSystem();

			$lastdate = date("Y-m-d H:i:s", mktime(0,0,0,date("m"),date("d")-5,date("Y")));
			if ($result = \MySQL::getDB()->getRow("	SELECT 	*
													FROM 	mapsignatures
													WHERE 	solarsystemid = ? AND sigid = ? AND authgroupid = ?
													AND		(deleted = 0 OR (deleted > 0 AND scandate < ?))"
								, array($systemID, $sigID, $chain->authgroupID, $lastdate)))
			{
				$signature = new \scanning\model\Signature();
				$signature->load($result);
				return $signature;
			}

			return null;
		}

		/**
		 * Check open signatures. Markeer volledig gescand indien geen openstaande sigs.
		 * @param \eve\model\SolarSystem $solarSystem
		 */
		function checkOpenSignatures($solarSystem)
		{
			\AppRoot::debug("checkOpenSignatures($solarSystem->name)");
			$openSignatures = array();
			foreach (\scanning\model\Signature::getSignaturesBySolarSystem($solarSystem->id) as $signature)
			{
				if ($signature->sigType == null || strlen(trim($signature->sigType)) == 0)
					$openSignatures[] = $signature;
			}
			\AppRoot::debug(count($openSignatures)." open signatures");
			if (count($openSignatures) == 0)
			{
				// Geen open sigs. Markeer volledig gescand.
				$wormhole = \scanning\model\Wormhole::getWormholeBySystemID($solarSystem->id, \User::getSelectedChain());
				if ($wormhole != null)
					$wormhole->markFullyScanned();
			}
		}
	}
}
?>