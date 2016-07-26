<?php
namespace stats\console;

class Kills
{
    function doImport($arguments=[])
    {
        $authgroup = null;
        if (count($arguments) > 0)
            $authgroup = new \admin\model\AuthGroup(array_shift($arguments));

        if ($authgroup)
        {
            $api = new \api\Client();
            $api->baseURL = "http://stats.limited-power.co.uk/api/";

            foreach ($authgroup->getAlliances() as $alliance)
            {
                \AppRoot::doCliOutput(" => ".$alliance->name);
                $result = $api->get("rethink/entity/".$alliance->id);
                if ($api->success())
                {
                    $data = json_decode($api->getResult());
                    foreach ($data as $dat)
                    {
                        $character = new \eve\model\Character($dat->characterID);
                        if ($character && $character->getUser() && $character->id == $character->getUser()->getMainCharacterID())
                        {
                            \AppRoot::doCliOutput("     ".$dat->reduction." kills for ".$character->getUser()->getFullName());
                            $stats = \stats\model\Kills::findOne(["userid" => $character->getUser()->id]);
                            if (!$stats)
                                $stats = new \stats\model\Kills();

                            $stats->userID = $character->getUser()->id;
                            $stats->nrKills = $dat->reduction;
                            $stats->store();
                        }
                    }
                }
                else
                    \AppRoot::doCliOutput("Api call failed", "red");
            }
        }
        else
            \AppRoot::doCliOutput("No access group selected", "red");
    }
}