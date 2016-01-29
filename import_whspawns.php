<?php
require_once("init.php");

if (file_exists("documents/import-wh-spawns.txt"))
{
    \AppRoot::setMaxExecTime(600);

    $systemTypes = array("c1" => 4,
        "c2" => 5,
        "c3" => 6,
        "c4" => 7,
        "c5" => 8,
        "c6" => 9,
        "high" => 1,
        "low" => 2,
        "null" => 3,
        "thera" => 10);

    \MySQL::getDB()->doQuery("TRUNCATE mapwormholetypespawns");
    $currentOrigin = null;
    foreach (file("documents/import-wh-spawns.txt") as $i => $line)
    {
        if (strlen(trim($line)) == 0)
            continue;

        if ($line[0] == "*")
        {
            $wh = strtolower(trim(str_replace("*","",$line)));
            \AppRoot::debug("Set origin: ".$wh);
            $currentOrigin = (isset($systemTypes[$wh])) ? $systemTypes[$wh] : null;
        }
        else
        {
            $parts = explode(" ", $line);
            $whType = \scanning\model\WormholeType::findByName(trim($parts[0]));
            if ($whType == null)
            {
                $whType = new \scanning\model\WormholeType();
                $whType->name = trim(strtoupper($parts[0]));

                if (!$whType->destination) {
                    if (isset($parts[1])) {
                        $desto = strtolower(trim(str_replace("(","", str_replace(")","", $parts[1]))));
                        if (isset($systemTypes[$desto]))
                            $whType->destination = $systemTypes[$desto];
                    }
                }
                $whType->store();
            }

            $data = [
                "whtypeid" => $whType->id,
                "fromclass" => $currentOrigin,
                "toclass" => $whType->destination
            ];
            \MySQL::getDB()->insert("mapwormholetypespawns", $data);
        }
    }


    echo "<p>Done</p>";
}
else
    echo "<p style='color:red;'>Import file not found</p>";


echo \AppRoot::printDebug();
?>