<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_modifier_date_full($date=false, $inclWeekDay=false, $fullWeekDay=false, $shortMonth=false)
{
	return \Tools::getFullDate($date, $inclWeekDay, $fullWeekDay, $shortMonth);
}

?>
