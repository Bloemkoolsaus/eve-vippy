<?php
namespace system\console;

class Logs
{
    function cleanupLogFiles()
    {
        \AppRoot::doCliOutput("Cleanup log files");
        foreach (\Tools::getFilesFromDirectory("logs", true) as $file) {
            $this->cleanLogDirectory($file);
        }
    }

    private function cleanLogDirectory($dir, $dept=0)
    {
        $debugPrefix = " ";
        for ($i=0; $i<($dept*3); $i++) {
            $debugPrefix .= " ";
        }
        \AppRoot::doCliOutput($debugPrefix." - ".$dir);
        if (is_file($dir)) {
            $time = filemtime($dir);
            if ($time < mktime(0,0,0,date("m"),date("d")-2,date("Y")))
            {
                \AppRoot::doCliOutput($debugPrefix." * Removing ".$dir);
                \Tools::deleteFile($dir);
            }
        } else {
            foreach (\Tools::getFilesFromDirectory($dir, true) as $file) {
                $this->cleanLogDirectory($file, $dept+1);
            }
        }
    }
}