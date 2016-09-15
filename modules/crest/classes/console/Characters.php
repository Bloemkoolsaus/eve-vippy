<?php
namespace crest\console;

class Characters
{
    function doImport()
    {
        \AppRoot::doCliCommand(" * Import Characters");

        if ($results = \MySQL::getDB()->getRows("select * from characters where updatedate < date_add(now(), interval -1 day)"))
        {
            foreach ($results as $result)
            {
                $character = new \crest\model\Character();
                $character->load($result);
                $character->importData();
            }
        }

        \AppRoot::doCliCommand("Finished");
        return true;
    }
}