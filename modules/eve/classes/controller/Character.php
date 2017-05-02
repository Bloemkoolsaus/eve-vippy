<?php
namespace eve\controller
{
	class Character
	{
		/**
		 * Get characters by UserID
		 * @param int $userID
		 * @return \eve\model\Character[]
		 */
		function getUserCharacters($userID)
		{
			$characters = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM characters WHERE userid = ?
													ORDER BY isceo DESC, isdirector DESC, name ASC"
											, array($userID)))
			{
				foreach ($results as $result)
				{
					$char = new \eve\model\Character();
					$char->load($result);
					$characters[] = $char;
				}
			}
			return $characters;
		}

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

            try
            {
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

                $corporation = new \eve\model\Corporation($character->corporationID);
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
            }
            catch (\Exception $e) { }

            $user = $character->getUser();
            if ($user) {
                $user->resetCache();
                $user->resetAuthGroups();
                if (!$user->getMainCharacter()->isAuthorized())
                    $user->resetMainCharacter();
            }

			return $character;
		}

		function getCharactersByUserID($userID)
		{
			$characters = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM characters WHERE userid = ? ORDER BY name", array($userID)))
			{
				foreach ($results as $result)
				{
					$char = new \eve\model\Character();
					$char->load($result);
					$characters[] = $char;
				}
			}
			return $characters;
		}
	}
}