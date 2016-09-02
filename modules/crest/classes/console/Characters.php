<?php
namespace crest\console;

class Characters
{
    function doImport()
    {
        $character = new \crest\model\Character(1899584653);
        $character->importData();
    }
}