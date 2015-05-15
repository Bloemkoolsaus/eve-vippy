<?php
namespace whinfo\view
{
	class Colors
	{
		function getOverview()
		{
			$tpl = \SmartyTools::getSmarty();
			return $tpl->fetch("whinfo/colors");
		}
	}
}
?>