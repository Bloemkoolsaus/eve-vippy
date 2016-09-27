<?php
namespace eve\controller
{
	class Character
	{
		private $db = null;

		function __construct()
		{
			$this->db = \MySQL::getDB();
		}

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
			$character = new \eve\model\Character($characterID);
            \AppRoot::doCliCommand("Import Character ".$character->name);

            // Public info
            $api = new \eve\controller\API();
            $api->setCharacterID($character->id);
            $result = $api->call("/eve/CharacterInfo.xml.aspx");

            if ($errors = $api->getErrors())
                return null;

            $character->id = (string)$result->result->characterID;
            $character->name = (string)$result->result->characterName;
            $character->corporationID = (string)$result->result->corporationID;
            $character->store();

			return $character;
		}

		function getCharactersByUserID($userID)
		{
			$characters = array();
			if ($results = $this->db->getRows("SELECT * FROM characters WHERE userid = ? ORDER BY name", array($userID)))
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