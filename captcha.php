<?php
session_start();

$chars = "abcdefghijkmnpqrstuvwxyz23456789";
$code = "";
while (strlen(trim($code)) < 5) {
	$code .= $chars[rand(1, strlen($chars))];
}
$_SESSION["antispam"] = md5(strtoupper($code));

$im = imagecreatetruecolor(160, 70);
$white = imagecolorallocate($im, 240, 240, 240);
$yellow = imagecolorallocate($im, 255, 200, 0);
$black = imagecolorallocate($im, 0, 0, 0);
imagefilledrectangle($im, 0, 0, 200, 35, $black);

$font = getcwd()."/DANZIG4P.TTF";
imagettftext($im, 35, 0, 20, 55, $yellow, $font, $code);
imagettftext($im, 35, 0, 15, 50, $white, $font, $code);

header("Expires: Wed, 1 Jan 1997 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

header ("Content-type: image/gif");
imagegif($im);
imagedestroy($im);
?>