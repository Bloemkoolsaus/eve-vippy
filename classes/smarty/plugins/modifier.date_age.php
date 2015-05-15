<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_modifier_date_age($date=false, $short=false)
{
	return \Tools::getAge($date, false, false, $short);
}

?>
