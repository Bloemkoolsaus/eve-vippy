<?php
namespace system\console;

class Backup
{
    function doDefault($arguments=[])
    {
        $systemController = new \system\controller\System();
        $systemController->doBackup();
        return true;
    }
}