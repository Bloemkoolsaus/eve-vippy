<?php
namespace crest\console;

class Characters
{
    function doImport($arguments=[])
    {
        \AppRoot::doCliOutput("Import Characters");

        $query = [];
        if (count($arguments) > 0) {
            $characterIDs = [];
            while (count($arguments) > 0) {
                $id = array_shift($arguments);
                if (is_numeric($id))
                    $characterIDs[] = $id;
            }
            $query[] = "c.id in (".implode(",", $characterIDs).")";
        } else
            $query[] = "c.updatedate <'".date("Y-m-d H:i:s", mktime(date("H")-6,date("i"),date("s"),date("m"),date("d"),date("Y")))."'";

        $i = 0;
        if ($results = \MySQL::getDB()->getRows("select c.*
                                                 from   characters c
                                                    inner join crest_token t on t.tokentype = 'character' and t.tokenid = c.id
                                                 where  ".implode(",", $query)."
                                                 order by c.updatedate asc"))
        {
            foreach ($results as $result) {
                $character = new \crest\model\Character();
                $character->load($result);
                \AppRoot::doCliOutput(" > ".$character->name);
                $character->importData();
                sleep(1);
                $i++;
            }
        }

        if ($i == 0)
        {
            \AppRoot::doCliOutput("No CREST characters imported. Update others");
            if ($results = \MySQL::getDB()->getRows("select * from characters where updatedate < ? order by updatedate asc"
                                , [date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d")-1,date("Y")))]))
            {
                foreach ($results as $result) {
                    $character = new \crest\model\Character();
                    $character->load($result);
                    \AppRoot::doCliOutput(" > ".$character->name);
                    $character->importData();
                    $i++;
                }
            }
        }

        \AppRoot::doCliOutput($i." characters imported");
        return "Finished";
    }
}