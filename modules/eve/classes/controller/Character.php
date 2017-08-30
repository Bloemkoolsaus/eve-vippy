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
        $character = new \crest\model\Character($characterID);
        \AppRoot::doCliOutput("Import Character " . $character->name);

        // Refresh CREST token
        /*
        $token = $character->getToken();
        if ($token)
            $valid = $token->isValid(true);
        */

        try {
            // Public info
            $api = new \eve\controller\API();
            $api->setCharacterID($characterID);
            $result = $api->call("/eve/CharacterInfo.xml.aspx");

            if ($errors = $api->getErrors())
                return null;

            \AppRoot::debug($result->result);
            $character->id = (string)$result->result->characterID;
            $character->name = (string)$result->result->characterName;
            $character->corporationID = (string)$result->result->corporationID;
            $character->store();

            $corporation = new \eve\model\Corporation((string)$character->corporationID);
            $corporation->id = (string)$result->result->corporationID;
            $corporation->name = (string)$result->result->corporationName;
            $corporation->allianceID = (int)$result->result->allianceID;
            $corporation->store();

            if ((int)$corporation->allianceID) {
                $alliance = new \eve\model\Alliance((string)$corporation->allianceID);
                $alliance->id = (string)$result->result->allianceID;
                $alliance->name = (string)$result->result->alliance;
                $alliance->store();
            }
        } catch (\Exception $e) { }

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