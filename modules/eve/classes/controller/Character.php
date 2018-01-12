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
        return $this->import($character);
    }

    /**
     * Import Character data from CCP
     * @param \eve\model\Character $character
     * @return \eve\model\Character
     */
    function import(\eve\model\Character $character)
    {
        \AppRoot::doCliOutput("Import Character " . $character->name);

        // Hebben we een token?
        $token = $character->getToken();
        if ($token)
        {
            // private call naar esi

        }
        else
        {
            // public call naar esi

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