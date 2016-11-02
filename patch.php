<?php
chdir(__DIR__);
require_once("init.php");
\AppRoot::readSqlUpdates();
\AppRoot::readPhpUpdates();