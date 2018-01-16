<?php
namespace eve\console;

class Character
{
    function doImport($arguments=[])
    {
        foreach ($arguments as $arg) {
            $character = \eve\model\Character::findByID($arg);
            if ($character) {
                $character->importData();
            }
        }
    }
}