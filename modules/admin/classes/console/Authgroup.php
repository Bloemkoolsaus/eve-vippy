<?php
namespace admin\console;

class Authgroup
{
    function doCleanup($arguments=[])
    {
        // In-actieve groepen weg doen
        \Approot::doCliOutput("Check and delete inactive authorization groups");
        foreach (\admin\model\AuthGroup::getAuthGroups() as $group) {
            if ($group->deleted) {
                if ($group->isActive()) {
                    $group->deleted = false;
                    $group->store();
                }
            } else {
                if (!$group->isActive()) {
                    $group->delete();
                }
            }
        }
    }
}