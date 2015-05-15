<?php
/*

Laat bedrag zien in geformateerde valuta. Bedrag moet een heel getal zijn (dus * 100), zonder decimalen.

 */

function smarty_modifier_number($amount, $div=0, $decimals=0)
{
	if (!is_numeric($amount))
		$amount = (int)$amount;

	if ($div != 0)
		$amount = $amount / $div;

	return number_format($amount, $decimals, ',', '.');
}
?>