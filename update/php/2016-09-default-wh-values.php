<?php
\MySQL::getDB()->insert("system_config", ["var" => "map_wormhole_width", "val" => 130]);
\MySQL::getDB()->insert("system_config", ["var" => "map_wormhole_height", "val" => 60]);
\MySQL::getDB()->insert("system_config", ["var" => "map_wormhole_offset_x", "val" => 20]);
\MySQL::getDB()->insert("system_config", ["var" => "map_wormhole_offset_y", "val" => 40]);