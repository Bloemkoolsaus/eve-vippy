<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_modifier_date_time($date=false, $format="H:i")
{
	return date($format, strtotime($date));
}

?>
