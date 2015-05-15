<?php
session_start();
require_once("../../../config/db.php");
require_once("../../../config/main.php");
require_once("../../../classes/AppRoot.class.php");
require_once("../../../classes/Tools.class.php");
require_once("../../../classes/User.class.php");
require_once("../../../classes/UserGroup.class.php");
require_once("../../../classes/SolarSystem.class.php");
require_once("../../../classes/MySQL.class.php");

mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS);
mysql_select_db(MYSQL_DTBS);

$_SESSION["mysql"] = new MySQL();
?>