<?php
namespace eve\model
{
	class API
	{
		public $keyID = 0;
		public $vCode;
		public $valid = false;
		public $banned = false;
		public $userID = 0;
		public $type;
		public $accesMask = 0;
		public $status;
		public $lastValidateDate = null;
		public $deleted = false;

		function __construct($keyID=false)
		{
			if ($keyID) {
				$this->keyID = $keyID;
				$this->load();
			}
		}

		function load($result=false)
		{
			if (!$result)
				$result = \MySQL::getDB()->getRow("SELECT * FROM api_keys WHERE keyid = ?", array($this->keyID));

			if ($result)
			{
				$this->keyID = $result["keyid"];
				$this->vCode = $result["vcode"];
				$this->valid = ($result["valid"]>0)?true:false;
				$this->banned = ($result["banned"]>0)?true:false;
				$this->deleted = ($result["deleted"]>0)?true:false;
				$this->userID = $result["userid"];
				$this->type = $result["apitype"];
				$this->accesMask = $result["accessmask"];
				$this->status = $result["status"];
				$this->lastValidateDate = (strlen(trim($result["lastcheckdate"]))>0) ? $result["lastcheckdate"] : null;
			}
		}

		function __get($who)
		{
			if ($who == "valid")
				return $this->isValid();

			if ($who == "status")
				return $this->getStatus();

			return null;
		}

		function store()
		{
			if (strlen(trim($this->keyID)) > 0 && strlen(trim($this->vCode)) > 0)
			{
				// Get old data. Check for differences!
				\AppRoot::debug("Compare old to new key info");
				$old = new \eve\model\API($this->keyID);
				if ($old->userID != 0 && $old->userID != $this->userID)
				{
					\AppRoot::debug("Api key user switch detected!");
					$user = new \users\model\User($this->userID);
					$user->addLog("apikey-owner-changed", $this->id,
							array(	"apikey" 	=> $this->keyID,
									"fromuser"	=> $old->userID,
									"touser"	=> $this->userID));
				}

				$data = array("keyid"	=> $this->keyID,
							"vcode"		=> $this->vCode,
							"valid"		=> ($this->valid)?1:0,
							"banned"	=> ($this->banned)?1:0,
							"deleted"	=> ($this->deleted)?1:0,
							"userid"	=> $this->userID,
							"apitype"	=> $this->type,
							"accessmask"=> $this->accesMask,
							"status"	=> $this->status,
							"lastcheckdate"	=> date("Y-m-d H:i:s", strtotime($this->lastValidateDate)),
							"updatedate"	=> date("Y-m-d H:i:s"));
				\MySQL::getDB()->updateinsert("api_keys", $data, array("keyid" => $this->keyID));
			}
		}

		function delete()
		{
			\MySQL::getDB()->update("api_keys", array("deleted" => 1), array("keyid" => $this->keyID));
		}

		function hasAccess($permission)
		{
			if (!is_numeric($permission))
				return array();

			$mask = $this->accesMask;
			while ($mask > 0) {
				for ($i=0, $n=0; $i<=$mask; $i=1*pow(2,$n), $n++) {
					$end = $i;
				}
				if ($end == $permission)
					return true;

				$mask = $mask - $end;
			}

			return false;
		}

		function isValid()
		{
			if (!$this->valid)
				return false;

			if ($this->banned)
				return false;

			return true;
		}

		function getStatus()
		{
			if ($this->banned)
				return "This key has been banned by a security reset.";

			return $this->status;
		}

		function validate($linkCharacters=true, $force=false)
		{
			if ($this->banned)
			{
				$this->valid = false;
				$this->status = "Banned";
				$this->lastValidateDate = date("Y-m-d H:i:s");
				$this->store();
				return false;
			}

			// Alleen valideren als het langer dan een uur geleden is.
			if (strtotime($this->lastValidateDate) < strtotime("now")-1440 || $force == true)
			{
				$api = new \eve\controller\API();
				$api->setKeyID($this->keyID);
				$api->setvCode($this->vCode);

				$result = $api->call("/account/APIKeyInfo.xml.aspx");

				$this->lastValidateDate = date("Y-m-d H:i:s");
				$this->status = "Last check: ".\Tools::getDayOfTheWeek().", ".\Tools::getWrittenMonth(date("m"))." ".date("d").", ".date("Y")." - ".date("H:i")."h";
				$this->valid = true;

				// Check voor errors
				if ($errors = $api->getErrors())
				{
					foreach ($errors as $code => $msg) {
						if ($code >= 200 && $code <= 299) {
							$this->status = $msg;
							$this->valid = false;
						}
					}

					// Er waren wel errors, maar geen authentication errors. Afbreken!
					if ($this->valid) {
						return $this->valid;
					}
				}
				$this->store();

				if (!$result)
					return $this->valid;

				// Zet type en accessMask;
				$this->type = strtolower((string)$result->result->key["type"]);
				$this->accesMask = (int)$result->result->key["accessMask"];

				// Het is wel een character api toch?
				if ($this->type == "corporation") {
					$this->valid = false;
					$this->status = "This key is not a `Character` type key";
				}

				// Toegang tot characterSheet?
				if (!$this->hasAccess(8)) {
					$this->valid = false;
					$this->status = "This key does not provide access to `Private Information` > `CharacterSheet`";
				}

				$this->store();

				if ($linkCharacters)
				{
					if ($this->valid)
					{
						// Characters koppelen
						foreach ($result->result->key->rowset->row as $row)
						{
							if (strlen(trim((string)$row["characterName"])) > 0)
							{
								$character = new \eve\model\Character((string)$row["characterID"]);
								$character->id = (string)$row["characterID"];
								$character->name = (string)$row["characterName"];
								$character->apiKeyID = $this->keyID;
								$character->userID = $this->userID;
								$character->corporationID = (string)$row["corporationID"];
								$character->store();

								$corporation = new \eve\model\Corporation((string)$row["corporationID"]);
								if ($corporation->ceoID == 0 || strtotime($corporation->updateDate) < strtotime("now")-86400)
								{
									$corpController = new \eve\controller\Corporation();
									$corpController->importCorporation($corporation->id);
								}

								$char = new \eve\controller\Character();
								$char->importCharacter($character->id);
							}
						}
					}
				}
			}

			$user = new \users\model\User($this->userID);

			// Reset main character als die niet meer geldig is
			if (!$user->getMainCharacter()->isAuthorized())
				$user->resetMainCharacter();

			$user->isValid = null;
			$user->fetchIsAuthorized();
			$user->updateDisplayName();

			return $this->valid;
		}


		/**
		 * Get api keys by userid
		 * @param integer $userID
		 * @return \eve\model\API[]
		 */
		public static function getApiKeysByUser($userID)
		{
			$keys = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM api_keys
													WHERE userid = ? AND deleted = 0
													ORDER BY keyid"
										, array($userID)))
			{
				foreach ($results as $result)
				{
					$key = new \eve\model\API();
					$key->load($result);
					$keys[] = $key;
				}
			}
			return $keys;
		}
	}
}
?>