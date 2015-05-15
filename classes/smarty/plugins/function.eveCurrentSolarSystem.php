<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {eveCurrentSolarSystem} function plugin
 *
 * Type:     function<br>
 * Name:     eveCurrentSolarSystem<br>
 * Purpose:  Get the current EVE-Online solar system
 * @return true or false
 */
function smarty_function_eveCurrentSolarSystem()
{
	return \eve\model\IGB::getIGB()->getSolarsystemName();
}
?>