<?php
namespace whinfo\view
{
	class Effects
	{
		function getOverview()
		{
			$effects = \eve\model\SolarSystemEffect::getEffects();

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("effects", $effects);
			return $tpl->fetch("whinfo/effects");
		}
	}
}
?>