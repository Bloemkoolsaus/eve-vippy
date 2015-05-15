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

		function importCharacter($characterID)
		{
			$character = new \eve\model\Character($characterID);
			if ($character->apiKeyID)
			{
				$apikey = new \eve\model\API($character->apiKeyID);
				if ($apikey->valid)
				{
					$api = new \eve\controller\API();
					$api->setKeyID($apikey->keyID);
					$api->setvCode($apikey->vCode);
					$api->setCharacterID($character->id);
					$result = $api->call("/char/CharacterSheet.xml.aspx");

					if ($errors = $api->getErrors())
					{
						// Foutje bedankt!
						return false;
					}
					else
					{
						// Updaten
						$character->id = (string)$result->result->characterID;
						$character->name = (string)$result->result->name;
						$character->apiKeyID = $apikey->keyID;
						$character->userID = $apikey->userID;
						$character->corporationID = (string)$result->result->corporationID;
						$character->dateOfBirth = (string)$result->result->DoB;

						// Titels / Director check / Skills
						$character->isDirector = false;

						\AppRoot::debug("character check director: ".$character->name);
						if ($character->isAuthorized())
						{
							if (isset($result->result->rowset))
							{
								foreach ($result->result->rowset as $rowset)
								{
									// Director check
									if ((string)$rowset["name"] == "corporationRoles") {
										foreach ($rowset->row as $row) {
											if ($row["roleName"] == "roleDirector")
												$character->isDirector = true;
										}
									}
								}
							}
							\AppRoot::debug("is director: ".($character->isDirector)?"yes":"no");
						}
						else
							\AppRoot::debug("not authorized");

						// Opslaan
						$character->store();

						return true;
					}
				}
				else
				{
					// Public info
					$api = new \eve\controller\API();
					$api->setCharacterID($character->id);
					$result = $api->call("/eve/CharacterSheet.xml.aspx");

					if ($errors = $api->getErrors())
					{
						// Foutje bedankt!
						return false;
					}
					else
					{
						$character->id = (string)$result->result->characterID;
						$character->name = (string)$result->result->characterName;
						$character->corporationID = (string)$result->result->corporationID;
						$character->store();
					}
				}
			}
			return false;
		}

		/**
		 * Get characters bij api-key
		 * @param integer $apiKeyID
		 * @return \eve\model\Character[]
		 */
		function getCharactersByApiKey($apiKeyID)
		{
			$characters = array();
			if ($results = $this->db->getRows("SELECT * FROM characters WHERE api_keyid = ? ORDER BY name", array($apiKeyID)))
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
?>