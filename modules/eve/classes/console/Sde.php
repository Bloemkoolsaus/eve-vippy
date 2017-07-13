<?php
namespace eve\console;

class Sde
{
    function doDefault($arguments=[])
    {

    }

    function doConvert($arguments=[])
    {
        $schema = (count($arguments)>0)?array_shift($arguments):\eve\Module::eveDB();
        \AppRoot::doCliOutput("Convert tables to lowercase on schema ".$schema);
        $database = new \MySQL([
            "host" => MYSQL_HOST,
            "user" => MYSQL_USER,
            "pass" => MYSQL_PASS,
            "dtbs" => $schema
        ]);
        $database->tableToLowercase();
    }
}