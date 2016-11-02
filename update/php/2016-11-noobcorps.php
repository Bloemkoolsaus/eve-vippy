<?php
\AppRoot::doCliOutput("Remove noobcorps from authgroups");
foreach (\admin\model\AuthGroup::getAuthGroups() as $group)
{
    \AppRoot::doCliOutput(" > ".$group->name);
    foreach ($group->getCorporations() as $corp)
    {
        \AppRoot::doCliOutput("   - ".$corp->name);
        if ($corp->isNPC())
            $group->removeCorporation($corp->id);

        $group->store();
    }
}