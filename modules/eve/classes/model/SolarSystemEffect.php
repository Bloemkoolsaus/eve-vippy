<?php
namespace eve\model
{
	class SolarSystemEffect extends \eve\model\Item
	{
		private $attributes = null;

		function getAttributes($whClass=null)
		{
			if ($this->attributes === null)
			{
				$negatives = $this->getAttributeNegativeTitles();
				$titles = $this->getAttributeTitleReplacements();


				$systemEffectName = $this->name;
				$systemEffectName = str_replace("Wolf-Rayet Star", "Wolf Rayet", $systemEffectName);

				$this->attributes = array();
				if ($results = \MySQL::getDB()->getRows("SELECT i.typename, t.attributename, t.displayname, a.valuefloat, t.unitid
														FROM 	".\eve\Module::eveDB().".dgmtypeattributes a
															LEFT JOIN ".\eve\Module::eveDB().".dgmattributetypes t ON t.attributeid = a.attributeid
															LEFT JOIN ".\eve\Module::eveDB().".invtypes i ON i.typeid = a.typeid
														WHERE 	i.typename LIKE '%".$systemEffectName."%'
														AND		i.typename LIKE '%Class ".(($whClass!=null)?$whClass:6)."%'"))
				{
					foreach ($results as $result)
					{
						$effect = new \stdClass();
						$effect->name = $result["typename"];
						$effect->attribute = $result["attributename"];
						$effect->title = $result["displayname"];
						$effect->value = $result["valuefloat"];
						$effect->unit = $result["unitid"];


						// Value
						if ($effect->unit == 109 || $effect->unit == 104)
							$effect->value = ($effect->value-1)*100;
						if ($effect->unit == 124 || $effect->unit == 105)
							$effect->value = $effect->value*-1;


						// Title
						if (isset($titles[$effect->attribute]))
							$effect->title = $titles[$effect->attribute];

						$names = array("bonus","multiplier","resistance","modifier");
						$nnames = array("","","resist","");

						$effect->title = str_replace($names,$nnames,strtolower($effect->title));
						$effect->title = trim(ucwords($effect->title));


						// Bonus / Nerf?
						if (in_array($effect->attribute, $negatives))
							$effect->bonus = ($effect->value >= 0) ? false : true;
						else
							$effect->bonus = ($effect->value >= 0) ? true : false;


						$this->attributes[] = $effect;
					}
				}
			}

			return $this->attributes;
		}

		private function getAttributeNegativeTitles()
		{
			return array("signatureRadiusMultiplier","heatDamageMultiplier");
		}

		private function getAttributeTitleReplacements()
		{
			return array(
					"armorDamageAmountMultiplier"		=> "Armor Repair amount multiplier",
					"shieldBonusMultiplier"				=> "Shield Repair amount multiplier",
					"armorDamageAmountMultiplierRemote" => "Remote armor repair amount multiplier",
					"shieldBonusMultiplierRemote"		=> "Remote shield repair amount multiplier",
					"signatureRadiusMultiplier"			=> "Signature radius",
					"agilityMultiplier"					=> "Agility Modifier",
					"maxRangeMultiplier"				=> "Targeting Range",
					"fallofMultiplier"					=> "Turret Falloff Modifier",
					"droneRangeMultiplier"				=> "Drone Control Range",
					"aoeVelocityMultiplier"				=> "Missile Explosion Velocity multiplier",
					"overloadBonusMultiplier"			=> "Overload boost",
					"droneRangeMultiplier"				=> "Drone Control Range",
					"droneRangeMultiplier"				=> "Drone Control Range",
					"energyTransferAmountBonus"			=> "Energy Transfer amount multiplier");
		}



		/**
		 * Get by groupid
		 * @return \eve\model\SolarSystemEffect[]
		 */
		public static function getEffects()
		{
			$items = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM ".\eve\Module::eveDB().".invtypes WHERE groupid = 995 ORDER BY typename"))
			{
				foreach ($results as $result)
				{
					$item = new self();
					$item->load($result);
					$items[] = $item;
				}
			}
			return $items;
		}
	}
}
?>