<?php
namespace scanning
{
	class Connection extends \scanning\model\Connection
	{
		/** STATIC **/

		public static function addConnection($from, $to, $chainID=false)
		{
			if (!$chainID)
				$chainID = \User::getSelectedChain();

			$chain = new \scanning\model\Chain();
			$chain->id = $chainID;
			return $chain->addWormholeConnection($from, $to);
		}

		public static function getWHTypeNameById($id)
		{
			$db = \MySQL::getDB();
			if ($result = $db->getRow("SELECT * FROM mapwormholetypes WHERE id = ?", array($id)))
				return $result["name"];
			else
				return "K162";
		}
	}
}
?>