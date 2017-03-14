<?php
namespace system\controller;

class System
{
    function doBackup()
    {
        \AppRoot::setMaxExecTime(6400);
        \AppRoot::setMaxMemory("1000M");
        $this->cleanupOldBackups();

        // Maak de backup.
        if (defined("MYSQL_BACKUP_HOST"))
        {
            $credentials = array("host" => MYSQL_BACKUP_HOST, "dtbs" => MYSQL_BACKUP_DTBS,
                                "user" => MYSQL_BACKUP_USER, "pass" => MYSQL_BACKUP_PASS);
        }
        else
        {
            $credentials = array("host" => MYSQL_HOST, "dtbs" => MYSQL_DTBS,
                                "user" => MYSQL_USER, "pass" => MYSQL_PASS);
        }

        $backupDB = new \MySQL($credentials);
        $backupDB->makeBackUp(false, $this->getBackupDir().date("YmdHi").".sql");
        $backupDB->close();
    }

    private function getBackupDir()
    {
        $directory = "documents/backups/";
        if (defined("BACKUPDIR"))
            $directory = BACKUPDIR;

        // Check of de map bestaat> Zo niet, maak em aan.
        $backupDirectory = "";
        foreach (explode("/", $directory) as $part)
        {
            if (strlen(trim($part)) > 0)
            {
                $backupDirectory .= $part;
                if (!file_exists($backupDirectory))
                    mkdir($backupDirectory, 0777);
            }
            $backupDirectory .= "/";
        }

        $backupDirectory = str_replace("//","/",$backupDirectory);
        return $backupDirectory;
    }

    private function cleanupOldBackups()
    {
        $directory = $this->getBackupDir();
        \AppRoot::doCliOutput("Clean old backups");
        if ($handle = opendir($directory))
        {
            while (false !== ($file = readdir($handle)))
            {
                if (!is_file($file) && $file != "." && $file != "..")
                {
                    \AppRoot::doCliOutput(" - ".$file);
                    $year = $file[0].$file[1].$file[2].$file[3];
                    $month = $file[4].$file[5];
                    $day = $file[6].$file[7];
                    $datestring = $year."-".$month."-".$day." 00:00:00";

                    if (strtotime($datestring) < mktime(0,0,0,date("m")-1,date("d"),date("Y")))
                    {
                        if (is_file($directory.$file))
                            @unlink($directory.$file);
                    }
                }
            }
        }
    }
}