<?php
namespace scanning\controller
{
	class Scanner
	{
		/**
		 * De location tracker!! Big Brother..
		 * Houd bij waar je bent
		 * @return integer solarsystemID van de huidige locatie.
		 */
		function locationTracker()
		{
			\AppRoot::debug("locationTracker() ---------------------");
			if (!\eve\model\IGB::getIGB()->isTrusted())
				return "Not in IGB or Not trusted";

			// Haal laaste locatie
			$chain = new \scanning\model\Chain(\User::getSelectedChain());
			$previousLocation = (isset($_SESSION["vippy_lastlocation"])) ? $_SESSION["vippy_lastlocation"]-0 : 0;
			$currentLocation = \eve\model\IGB::getIGB()->getSolarsystemID()-0;

			// Set new previous location
			$_SESSION["vippy_lastlocation"] = $currentLocation;

			// We jumpen naar een ander systeem!
			if ($previousLocation != $currentLocation)
			{
				\AppRoot::debug("We moved");

				// Zitten we in een POD?? Dan niet registreren!!
				if (in_array(\eve\model\IGB::getIGB()->getShiptypeID(), array(0, 670, 33328)))
					return \eve\model\IGB::getIGB()->getSolarsystemID();

				$addToChain = false;
				$systemController = new \eve\controller\SolarSystem();

				$fromSystem = new \scanning\model\System($previousLocation);
				$toSystem = new \scanning\model\System($currentLocation);

				if ($fromSystem->isWSpace() || $toSystem->isWSpace())
					$addToChain = true;
				else
				{
					// 2 k-space. Als er geen gate tussen zit, is het wss wel een wormhole.
					if ($fromSystem->getNrJumpsTo($toSystem->id) > 1)
						$addToChain = true;
				}


				// Check of een van de systemen op de chain staat.
				$fromWhID = \scanning\Wormhole::getWormholeIdBySystem($previousLocation, $chain->id);
				$toWhID = \scanning\Wormhole::getWormholeIdBySystem($currentLocation, $chain->id);


				// Als beide systemen er op staan. Geen nieuwe wh toevoegen..
				if ($fromWhID !== false && $toWhID !== false)
					$addToChain = false;

				if ($fromWhID != false || $toWhID != false)
				{
					if ($addToChain) {
						// Nieuwe wormhole toevoegen.
						$chain->addWormholeSystem($previousLocation, $currentLocation);
					} else {
						// Cache resetten van mensen zodat de systemjump op de map komt.
						$chain->setMapUpdateDate();
					}
				}

				// Registreer jump
				$connection = \scanning\model\Connection::getConnectionByLocations($previousLocation, $currentLocation, $chain->id);
				if ($connection !== null)
				{
					$connection->addJump(\eve\model\IGB::getIGB()->getShiptypeID(), \eve\model\IGB::getIGB()->getPilotID(),
										$chain->id, $previousLocation, $currentLocation);
				}
			}

			// Reset huidige locatie
			\MySQL::getDB()->insert("mapwormholecharacterlocations",
									array(	"characterid"	=> \eve\model\IGB::getIGB()->getPilotID(),
											"solarsystemid"	=> \eve\model\IGB::getIGB()->getSolarsystemID(),
											"shiptypeid"	=> \eve\model\IGB::getIGB()->getShiptypeID(),
											"lastdate"		=> date("Y-m-d H:i:s"),
											"authgroupid"	=> $chain->authgroupID),
									array(	"characterid"	=> \eve\model\IGB::getIGB()->getPilotID()));

			\AppRoot::debug("/locationTracker() ---------------------");
			return $currentLocation;
		}

		/**
		 * Copy paste anomalies.
		 * @return string
		 */
		function getCopyPasteAnomalyForm()
		{
			if (\Tools::POST("copypasteanomalies"))
			{
				$this->parseCopyPastedAnomalySigs(\Tools::POST("copypasteanomalies"));
				\AppRoot::redirect("index.php?module=scanning#signatures");
			}

			$tpl = \SmartyTools::getSmarty();
			return $tpl->fetch(\SmartyTools::getTemplateDir("scanning")."copypasteanoms.html");
		}

		/**
		 * Copy paste signatures
		 * @return string
		 */
		function getCopyPastSignatureForm()
		{
			if (isset($_POST["copypastesignatures"]))
			{
				$this->parseCopyPastedAnomalySigs(\Tools::POST("copypastesignatures"));
				\AppRoot::redirect("index.php?module=scanning#signatures");
			}
			return "";
		}

		function deleteAllSignatures($wormholeID)
		{
			$wormhole = new \scanning\model\Wormhole($wormholeID);
			foreach (\scanning\model\Signature::getSignaturesBySolarSystem($wormhole->solarSystemID) as $sig)
			{
				if (trim(strtolower($sig->sigType)) !== "pos")
					$sig->delete();
			}
			return true;
		}

		/**
		 * Parse copy pasted anomalies or signatures
		 * @param string $imput
		 */
		private function parseCopyPastedAnomalySigs($imput)
		{
			$signatureController = new \scanning\controller\Signature();
			$solarSystem = new \scanning\model\System(\User::getSelectedSystem());

			$nrSignatures = 0;
			foreach (explode("\n",$imput) as $line)
			{
				$parts = explode("\t", $line);

				$sigID = strtoupper($parts[0][0].$parts[0][1].$parts[0][2]);
				$sigName = strtolower(trim($parts[3]));

				if (strlen(trim($sigID)) == 0)
					continue;

				if (strpos(strtolower($line),"cosmic signature") !== false)
				{
					$signature = $signatureController->getSignatureBySigID($sigID, $solarSystem->id);
					if ($signature == null)
						$signature = new \scanning\model\Signature();

					$signature->sigID = $sigID;
					$signature->solarSystemID = $solarSystem->id;

					$sigTypePasted = strtolower(trim(str_replace("site","",strtolower($parts[2]))));
					if (strlen(trim($sigTypePasted)) > 0)
					{
						$sigTypePasted = $sigTypePasted == "wormhole"? "WH": $sigTypePasted;
						$signature->sigType = $sigTypePasted;
					}
					$signature->sigInfo = (trim($signature->sigInfo) != "") ? $signature->sigInfo : $sigName;
					$signature->signalStrength = str_replace("%","",$parts[4]);
					$signature->deleted = false;
					$signature->store();

					$nrSignatures++;
				}
				else
				{
					// Anomaly
					$sigID = strtoupper($parts[0][0].$parts[0][1].$parts[0][2]);
					$sigType = $parts[1];

					// Check eerst of de sig al bestaat.
					if ($existID = \scanning\Anomaly::checkSignatureID($sigID))
						$anomaly = new \scanning\Anomaly($existID);
					else
						$anomaly = new \scanning\Anomaly();

					// Check of de anomaly type al bestaat
					if (!$anomID = \scanning\AnomalyType::getAnomalyIdByName($sigName))
					{
						$anom = new \scanning\AnomalyType();
						$anom->name = $sigName;
						$anom->type = $sigType;
						$anom->store();
						$anomID = $anom->id;
					}

					$anomaly->solarSystemID = $solarSystem->id;
					$anomaly->chainID = \User::getSelectedChain();
					$anomaly->anomalyID = $anomID;
					$anomaly->signatureID = $sigID;
					$anomaly->store();
				}
			}

			if ($nrSignatures > 2)
			{
				foreach (\scanning\model\Signature::getSignaturesBySolarSystem($solarSystem->id) as $sig)
				{
					if (strtotime($sig->updateDate) < strtotime("now")-2)
					{
						$sig->delete();

						$nameParts = explode(" ", $sig->sigInfo);
						$nameParts = explode("-", $nameParts[0]);
						$sigName = (isset($nameParts[1]))?$nameParts[1]:$nameParts[0];

						$wormhole = \scanning\model\Wormhole::getWormholeBySystemByName($sigName);
						if ($wormhole != null)
						{
							$connection = $sig->getWormhole()->getConnectionTo($wormhole->solarSystemID);
							if ($connection != null)
								$connection->delete();
						}
					}
				}
			}

			$signatureController->checkOpenSignatures($solarSystem);
		}
	}
}
?>