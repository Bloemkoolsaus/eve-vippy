<?php
namespace scanning
{
	class Wormhole extends \scanning\model\Wormhole
	{
		public static $defaultWidth = 130;
		public static $defaultHeight = 40;
		public static $defaultOffset = 30;

		public static function getWormholeByField($byfield,$byvalue,$chainid,$getfield="id")
		{
			$db = \MySQL::getDB();
			$byfield = \MySQL::escape($byfield);
			$byvalue = \MySQL::escape($byvalue);
			$getfield = \MySQL::escape($getfield);

			if ($result = $db->getRow("SELECT ".$getfield." FROM mapwormholes WHERE ".$byfield." = ? AND chainid = ?"
								, array($byvalue,$chainid)))
				return $result[$getfield];
			else
				return false;
		}

		public static function getWormholeIdBySystem($system,$chainid=false)
		{
			if (!$chainid)
				$chainid = \User::getSelectedChain();

			return self::getWormholeByField("solarsystemid",$system,$chainid);
		}

		public static function getWormholeByCoordinates($x, $y, $chainID=false)
		{
			\AppRoot::debug("getWormholeByCoordinates($x,$y,$chainID)");
			if (!$chainID)
				$chainID = \User::getSelectedChain();

			$leftX = $x;
			$rightX = $x + self::$defaultWidth;

			$topY = $y;
			$botY = $y + self::$defaultHeight;

			if ($result = \MySQL::getDB()->getRow("	SELECT 	id
													FROM 	mapwormholes
													WHERE	chainid = ".$chainID."
													AND		((x = ".$leftX." AND y = ".$topY.")
														OR		(x BETWEEN ".$leftX." AND ".$rightX."
															AND	y BETWEEN ".$topY." AND ".$botY.")
														OR		(".$leftX." BETWEEN x AND (x+".self::$defaultWidth.")
															AND	".$botY." BETWEEN y AND (y+".self::$defaultHeight."))
														OR		(".$topY." BETWEEN y AND (y+".self::$defaultHeight.")
															AND	(x+".self::$defaultWidth.") BETWEEN ".$leftX." AND ".$rightX.")
														OR		(".$topY." BETWEEN y AND (y+".self::$defaultHeight.")
															AND	x BETWEEN ".$leftX." AND ".$rightX."))"))
			{
				return $result["id"];
			}

			return false;
		}
	}
}
?>