<?php
\AppRoot::setMaxExecTime(0);
\AppRoot::setMaxMemory("2G");

if ($results = \MySQL::getDB()->getRows("select * from stats_whmap where chainid = 0"))
{
    foreach ($results as $result)
    {
        $stat = new \stats\model\Whmap();
        $stat->load($result);

        if ($userlog = \MySQL::getDB()->getRow("select * from user_log
                                                where what = 'add-wormhole'
                                                and userid = ? and whatid = ?"
                                    , [$stat->userID, $stat->systemID]))
        {
            $log = new \users\model\Log();
            $log->load($userlog);
            if ($log->extraInfo) {
                $info = json_decode($log->extraInfo);
                if (isset($info->chain) && isset($info->chain->id)) {
                    $stat->chainID = $info->chain->id;
                    $stat->store();
                }
            }
        }
    }
}