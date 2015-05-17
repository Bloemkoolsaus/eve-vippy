<?php
\MySQL::getDB()->update("user_config", array("val" => strtotime("now")), array("var" => "patchnotes"));
?>