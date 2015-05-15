<?php
namespace whinfo\view
{
	class Shattered
	{
		function getOverview()
		{
			$tpl = \SmartyTools::getSmarty();
			return $tpl->fetch("whinfo/shattered");
		}
	}
}
?>