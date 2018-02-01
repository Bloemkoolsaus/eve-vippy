<?php
namespace eve\controller;

class Character
{
    /**
     * Import Character
     * @param $characterID
     * @return \eve\model\Character|null
     */
    function importCharacter($characterID)
    {
        $corpController = new \eve\controller\Corporation();
        $character = \eve\model\Character::findByID($characterID);
        \AppRoot::doCliOutput("Import Character " . $character->name);

        // Import public info
        $esi = new \esi\Api();
        $esi->get("v4/characters/".$character->id."/");
        if ($esi->success()) {
            // Check of de corp gewijzigd is.
            if ($character) {
                if ($character->corporationID != $esi->getResult()->corporation_id) {
                    $oldCorp = \eve\model\Corporation::findByID($character->corporationID);
                    if ($oldCorp)
                        $corpController->importCorporation($oldCorp->id);
                }
            } else {
                $character = new \eve\model\Character();
                $character->id = $characterID;
            }

            $character->name = $esi->getResult()->name;
            $character->corporationID = $esi->getResult()->corporation_id;
            $character->store();

            // Corp updaten
            $corpController->importCorporation($esi->getResult()->corporation_id);
        }

        // Reset user
        $user = $character->getUser();
        if ($user) {
            $user->resetCache();
            $user->resetAuthGroups();
            if (!$user->getMainCharacter()->isAuthorized())
                $user->resetMainCharacter();

            // Reset status
            $user->isValid = null;
            $user->store();
        }

        return $character;
    }
}