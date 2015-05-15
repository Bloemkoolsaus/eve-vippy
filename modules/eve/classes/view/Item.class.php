<?php
namespace eve\view
{
	class Item
	{
		function getShowInfo($id)
		{
			$item = new \eve\model\Item($id);

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("item", $item);
			return $tpl->fetch("eve/items/showinfo");
		}
	}
}
?>