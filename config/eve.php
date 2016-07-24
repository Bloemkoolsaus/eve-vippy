<?php
// CONFIGURATION
define("API_ROOT", "https://api.eveonline.com");
define("API_CACHE_DIR", "logs/api/");
define("CREST_URL", "http://public-crest.eveonline.com/");
define("CANCELONCACHE", false);

$eveIGB = new \eve\model\IGB();
define("IS_EVE_IGB", $eveIGB->isIGB());
?>
