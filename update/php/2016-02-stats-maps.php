<?php
\AppRoot::setMaxExecTime(0);
\AppRoot::setMaxMemory("2G");

if ($results = \MySQL::getDB()->getRows("select * from user_log where what = 'add-wormhole' and lastdate > '2016-01-01'"))
{
    foreach ($results as $result)
    {
        $log = new \users\model\Log();
        $log->load($result);

        $stat = new \stats\model\Whmap();
        $stat->userID = $log->userID;
        $stat->pilotID = $log->pilotID;
        $stat->corpID = $log->getUser()->getMainCorporationID();
        $stat->mapdate = $log->logDate;

        if ($log->extraInfo)
        {
            $info = json_decode($log->extraInfo);
            if (isset($info->chain) && isset($info->chain->id))
                $stat->chainID = $info->chain->id;
            if (isset($info->system) && isset($info->system->id))
                $stat->systemID = $info->system->id;
        }

        $stat->store();
    }
}