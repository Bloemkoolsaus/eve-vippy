<?php
namespace scanning\controller
{
	class System extends \eve\controller\SolarSystem
	{
		function getWHDetailsTradehubs($systemID)
		{
			$hubs = array();
			$wormhole = new \scanning\model\Wormhole($systemID);
			$system = new \scanning\model\System($systemID);
			$controller = new \eve\controller\SolarSystem();

			if (!$system->isWSpace())
			{
				foreach ($this->getTradeHubs() as $id)
				{
					$csystem = new \eve\model\SolarSystem($id);
					$hubs[] = array("name"	=> $csystem->name,
									"jumps"	=> $system->getNrJumpsTo($csystem->id));
				}
			}
			return json_encode($hubs);
		}

		function getWHDetailsActivity($systemID)
		{
			\AppRoot::debug("getWHDetailsActivity($systemID)");
			$system = new \scanning\model\System($systemID);

			$filename = $system->buildActivityGraph();
			$generated = \Tools::getAge($system->getActivityGraphAge());

			return json_encode(array("url" => $filename, "date" => "<b>Graph age:</b> &nbsp; ".strtolower($generated)));
		}

		function getWHEffectsData($systemID)
		{
			$system = new \scanning\model\System($systemID);

			$negativePositives = array("signatureRadiusMultiplier","heatDamageMultiplier");
			$typeNameReplacements = array("armorDamageAmountMultiplier"		=> "Armor Repair amount multiplier",
										"shieldBonusMultiplier"				=> "Shield Repair amount multiplier",
										"armorDamageAmountMultiplierRemote" => "Remote armor repair amount multiplier",
										"shieldBonusMultiplierRemote"		=> "Remote shield repair amount multiplier",
										"signatureRadiusMultiplier"			=> "Signature radius",
										"agilityMultiplier"			=> "Agility Modifier",
										"maxRangeMultiplier"		=> "Targeting Range",
										"fallofMultiplier"			=> "Turret Falloff Modifier",
										"droneRangeMultiplier"		=> "Drone Control Range",
										"aoeVelocityMultiplier"		=> "Missile Explosion Velocity multiplier",
										"overloadBonusMultiplier"	=> "Overload boost",
										"droneRangeMultiplier"		=> "Drone Control Range",
										"droneRangeMultiplier"		=> "Drone Control Range",
										"energyTransferAmountBonus"	=> "Energy Transfer amount multiplier");

			$names = array("bonus","multiplier","resistance","modifier");
			$nnames = array("","","resist","");

			$effectName = "";
			$positives = array();
			$negatives = array();

			if (!$sysEffect = $system->getEffect())
			{
				// Geen effect..!!
				return "";
			}

			if ($sysEffect == "Wolf-Rayet Star")
				$sysEffect = "Wolf Rayet";
			if ($sysEffect == "Pulsar")
				$negativePositives[] = "rechargeRateMultiplier";

			$sysClassNr = $system->getClass(true);
			$sysClassNr = str_replace("C","",$sysClassNr);

			$cacheFileName = "solarsystem/".$system->id."/wheffects.json";

			if ($cache = \Cache::file()->get($cacheFileName))
				$results = json_decode($cache, true);
			else
			{
				if ($results = \MySQL::getDB()->getRows("SELECT i.typename, t.attributename, t.displayname, a.valuefloat, t.unitid
														FROM 	".\eve\Module::eveDB().".dgmtypeattributes a
															INNER JOIN ".\eve\Module::eveDB().".dgmattributetypes t ON t.attributeid = a.attributeid
															INNER JOIN ".\eve\Module::eveDB().".invtypes i ON i.typeid = a.typeid
														WHERE 	i.typename LIKE '%".$sysEffect."%'
														AND		i.typename LIKE '%Class ".$sysClassNr."%'"))
				{
                    \Cache::file()->set($cacheFileName, json_encode($results));
				}
			}

			if ($results)
			{
				foreach ($results as $result)
				{
					$effectName = $result["typename"];
					$effectType = $result["attributename"];
					$effectTypeName = $result["displayname"];

					$amount = $result["valuefloat"];
					if ($result["unitid"] == 109 || $result["unitid"] == 104)
						$amount = ($amount-1)*100;
					if ($result["unitid"] == 124 || $result["unitid"] == 105)
						$amount = $amount*-1;

					foreach ($typeNameReplacements as $type => $name)
					{
						if ($effectType == $type)
							$effectTypeName = $name;
					}

					$effectTypeName = str_replace($names,$nnames,strtolower($effectTypeName));
					$effectTypeName = trim(ucwords($effectTypeName));

					$effect = array("name" => $effectTypeName, "amount" => $amount);

					if (in_array($effectType, $negativePositives))
					{
						if ($amount >= 0)
							$negatives[] = $effect;
						else
							$positives[] = $effect;
					}
					else
					{
						if ($amount >= 0)
							$positives[] = $effect;
						else
							$negatives[] = $effect;
					}
				}
			}

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("effectname", $effectName);
			$tpl->assign("positives", $positives);
			$tpl->assign("negatives", $negatives);
			return $tpl->fetch("scanning/wheffectsdata");
		}

		function getWHDetailsPopup($systemID)
		{
			$system = new \scanning\model\System($systemID);

			$cacheFile = "documents/statistics/systeminfo/".$system->id.".html";
			if (!file_exists($cacheFile) || \AppRoot::doDebug())
			{
				$tpl = \SmartyTools::getSmarty();

				$title = $system->getTitleOnChain(\User::getSelectedChain());
				if ($title)
					$tpl->assign("systemtitle", $title);

				$tpl->assign("system", $system);

				if ($system->isWSpace())
					$tpl->assign("whEffectsData", self::getWHEffectsData($system->id));
				else
				{
					$tradehubTpl = \SmartyTools::getSmarty();
					$tradehubTpl->assign("tradehubs", json_decode(self::getWHDetailsTradehubs($system->id),true));
					$tpl->assign("tradehubData", $tradehubTpl->fetch("scanning/system/tradehub"));
				}

				$cache = $tpl->fetch("scanning/system/details");

				// Goede map aanmaken
				if (!file_exists("documents"))
					mkdir("documents",0777);
				if (!file_exists("documents/statistics"))
					mkdir("documents/statistics",0777);
				if (!file_exists("documents/statistics/systeminfo"))
					mkdir("documents/statistics/systeminfo",0777);

				$file = fopen($cacheFile,"w");
				fwrite($file,$cache);
				fclose($file);
			}

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("system", $system);
			$tpl->assign("wormhole", \scanning\model\Wormhole::getWormholeBySystemID($system->id, \User::getSelectedChain()));
			return $tpl->fetch($cacheFile);
		}
	}
}
?>