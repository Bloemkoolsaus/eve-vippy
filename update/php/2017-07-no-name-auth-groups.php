<?php
\AppRoot::setMaxExecTime(0);
\AppRoot::doCliOutput("Fix authorization groups with no name");
if ($results = \MySQL::getDB()->getRows("select * from user_auth_groups where name is null or length(trim(name)) = 0")) {
    foreach ($results as $result) {
        $authgroup = new \admin\model\AuthGroup();
        $authgroup->load($result);
        if (strlen(trim($authgroup->name)) == 0) {
            $alliances = $authgroup->getAlliances();
            if (count($alliances) > 0)
                $authgroup->name = array_shift($alliances)->name;
            else {
                $corporations = $authgroup->getCorporations();
                if (count($corporations) > 0)
                    $authgroup->name = array_shift($corporations)->name;
            }
            \AppRoot::doCliOutput("> ".$authgroup->name);
            $authgroup->store();
        }
    }
}