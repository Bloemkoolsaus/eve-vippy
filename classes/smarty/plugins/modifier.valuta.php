<?php
/*

Laat bedrag zien in geformateerde valuta. Bedrag moet een heel getal zijn (dus * 100), zonder decimalen.

 */

function smarty_modifier_valuta($amount, $short=false, $div=100)
{
	if (!is_numeric($amount))
		$amount = (int)$amount;

	if ($div != 0)
		$amount = $amount / $div;

	if ($short)
	{
		if ($amount == 0)
			return 0;

		$levels = array("b" => 1000000000,
						"m" => 1000000,
						"k" => 1000);
		foreach ($levels as $unit => $lvl) {
			if ($amount >= $lvl || $amount <= $lvl*-1)
				return ($amount/$lvl).$unit;
		}
	}

	return number_format($amount, 2, ',', '.');
}
?>