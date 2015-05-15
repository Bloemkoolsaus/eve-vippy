<?php
/*

Laat bedrag zien in geformateerde valuta. Bedrag moet een heel getal zijn (dus * 100), zonder decimalen.

 */

function smarty_modifier_valuta($amount, $div=100)
{
	if (!is_numeric($amount))
		$amount = (int)$amount;

	if ($div != 0)
		$amount = $amount / $div;

	return number_format($amount, 2, ',', '.');
}
?>