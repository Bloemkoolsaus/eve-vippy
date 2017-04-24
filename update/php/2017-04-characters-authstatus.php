<?php
\AppRoot::setMaxExecTime(0);
\AppRoot::doCliOutput("Save characters auth-status");
$characters = \eve\model\Character::findAll();
foreach ($characters as $c => $character) {
    \AppRoot::doCliOutput(" > (".$c." / ".count($characters).") [".$character->getCorporation()->ticker."] ".$character->name);
    $character->isAuthorized(true);
}