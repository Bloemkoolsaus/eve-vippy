<?php
namespace scanning\view
{
	class Chain
	{
		function getExitFinder()
		{
			$system = \eve\model\SolarSystem::getSolarsystemByName(\Tools::REQUEST("system"));

			$chainController = new \scanning\controller\Chain();
			$exits = $chainController->getExitsSortedBySystem($system->id);

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("system", $system);
			$tpl->assign("exits", $exits);
			return $tpl->fetch("scanning/exitfinder/exits");
		}
	}
}
?>