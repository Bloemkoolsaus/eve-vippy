<?php
namespace map\console;

class Expiry
{
    function doDefault($arguments=[])
    {
        // Delete expired connections
        \AppRoot::doCliOutput("Auto-expire connections and systems");
        foreach (\map\model\Map::findAll() as $map) {
            if ($map->getSetting("auto-expiry")) {
                foreach (\map\model\Connection::findAll(["chainid" => $map->id]) as $connection) {
                    if ($connection->normalgates)
                        continue;
                    if ($connection->isExpired())
                        $connection->delete();
                    elseif (!$connection->eol) {
                        if ($connection->getExpireStatus() == "eol") {
                            $connection->eol = true;
                            $connection->store();
                        }
                    }
                }
            }
        }
    }
}