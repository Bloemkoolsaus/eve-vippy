<?php
namespace users\console;

class Logs
{
    function doParse($arguments=[])
    {
        \AppRoot::doCliOutput("Parse userlog files");
        $cache = new \system\model\cache\File();
        foreach (\Tools::getFilesFromDirectory($cache->getDirectory()."userlog") as $file)
        {
            // Ouder dan 24 uur?
            $time = filemtime($file);
            if ((strtotime("now")-$time) < (3600*24)) {
                \AppRoot::doCliOutput(" > ".$file);
                $data = file_get_contents($file);
                $data = json_decode($data);
                $fileParts = explode("/", $file);

                \MySQL::getDB()->insert("user_log", [
                    "userid"	=> $data->userid,
                    "pilotid"	=> $data->pilotid,
                    "lastdate"	=> $data->lastdate,
                    "logdate"	=> $data->startdate,
                    "what"		=> $data->what,
                    "whatid"    => $data->whatid,
                    "ipaddress"	=> "localhost",
                    "sessionid"	=> array_pop($fileParts),
                    "useragent"	=> "CommandLine"
                ]);
                \Tools::deleteFile($file);
            }
        }
    }
}