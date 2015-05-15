<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_modifier_date_date($date=false, $format="d-m-Y")
{
	$timestamp = strtotime($date);
	if ($timestamp > 0)
		return date($format, $timestamp);

	return "";
}

?>
