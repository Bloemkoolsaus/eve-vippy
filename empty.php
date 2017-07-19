<?php
chdir(__DIR__);
require_once("init.php");

\AppRoot::doCliOutput("Empty all tables");
if ($results = \MySQL::getDB()->getRows("select * from information_schema.tables where table_schema = ?", [MYSQL_DTBS])) {
    foreach ($results as $result) {
        if ($result["table_name"] == "system_config")
            continue;
        \AppRoot::doCliOutput(" > ".$result["table_name"]);
        \MySQL::getDB()->doQuery("truncate ".$result["table_name"]);
    }
}