<?php
namespace crest\console;

class Characters
{
    function doImport()
    {
        \AppRoot::doCliOutput("Import Characters");

        if ($results = \MySQL::getDB()->getRows("select c.*
                                                 from   characters c
                                                    inner join crest_token t on t.tokentype = 'character'
                                                                            and t.tokenid = c.id
                                                 -- where  c.updatedate < date_add(now(), interval -1 day)"))
        {
            foreach ($results as $result)
            {
                $character = new \crest\model\Character();
                $character->load($result);
                \AppRoot::doCliOutput(" * ".$character->name);

                $token = $character->getToken();
                if ($token->isExpired())
                    $token->refresh();

                //$character->importData();
            }
        }

        \AppRoot::doCliOutput("Finished");
        return true;
    }
}