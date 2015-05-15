<?php
namespace whinfo\view
{
	class Mass
	{
		function getOverview()
		{
			$wormholes = array();
			if ($results = \MySQL::getDB()->getRows("SELECT w.id, w.name, w.whtype, w.lifetime, w.jumpmass, w.maxmass,
															s.name as desto, s.tag, s.color
													FROM	mapwormholetypes w
														LEFT JOIN mapsolarsystemclasses s ON s.id = w.destination
													ORDER BY w.name"))
			{
				foreach ($results as $result)
				{
					$wormholes[] = $result;
				}
			}

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("wormholes", $wormholes);
			return $tpl->fetch("whinfo/mass");
		}
	}
}
?>