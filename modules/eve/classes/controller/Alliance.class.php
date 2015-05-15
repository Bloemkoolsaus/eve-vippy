<?php
namespace eve\controller
{
	class Alliance
	{
		/**
		 * Get alliance by name
		 * @param string $name
		 * @return \eve\model\Alliance
		 */
		function getAllianceByName($name)
		{
			$alliance = new \eve\model\Alliance();
			if ($result = \MySQL::getDB()->getRow("SELECT * FROM alliances WHERE name = ?", array($name)))
				$alliance->load($result);

			return $alliance;
		}
	}
}
?>