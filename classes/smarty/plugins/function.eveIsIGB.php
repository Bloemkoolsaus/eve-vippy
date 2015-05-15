<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {eveIsIGB} function plugin
 *
 * Type:     function<br>
 * Name:     eveIsIGB<br>
 * Purpose:  Detect if visitor is in the EVE-Online ingame browser or not.
 * @return true or false
 */
function smarty_function_eveIsIGB()
{
	return \eve\model\IGB::getIGB()->isIGB();
}
?>