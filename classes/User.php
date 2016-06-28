<?php
class User extends \users\model\User
{
	private static $currentUser = null;

	public static function getLoggedInUserId()
	{
		\AppRoot::debug("getLoggedInUserId()");
		if (isset($_SESSION["vippy_userid"]))
		{
			\AppRoot::debug("logged in as ".$_SESSION["vippy_userid"]);
			return $_SESSION["vippy_userid"];
		}
		else
		{
			\AppRoot::debug("not logged in");
			return 0;
		}
	}

	/**
	 * Get current logged in user object
	 * @return \users\model\User
	 */
	public static function getUSER()
	{
		if (self::$currentUser == null || self::$currentUser->id == 0)
		{
			if ($userID = self::getLoggedInUserId())
				self::setUSER($userID);
			else
				self::setUSER();
		}

		return self::$currentUser;
	}

	public static function setUSER($userID=false)
	{
		\AppRoot::debug("setUSER(".$userID.")");
		self::$currentUser = new \users\model\User($userID);
	}

	public static function generatePassword($password, $check=false)
	{
		$saltLength = 20;
		$salt = "";

		if ($check) {
			$salt = substr($check, 0, $saltLength);
			$check = substr($check, $saltLength, strlen($check)-$saltLength);
		} else {
			$characters = "0123456789abcdefghijklmnopqrstuvwxyz";
			for ($i=0; $i<$saltLength; $i++) {
				$nr = rand(0, strlen($characters));
				$salt .= substr($characters, $nr-1, 1);
	    	}
		}

		$password = sha1($password);
		$password = $salt . sha1($password.$salt);

		return $password;
	}

	public static function getUserByField($value, $searchField="code", $getField="id")
	{
		if (strlen(trim($value)) == 0)
			return false;

		$db = MySQL::getDB();
		if ($user = $db->getRow("SELECT ".$getField." FROM users WHERE ".$searchField." = ?", array($value)))
			return $user[$getField];
		else
			return false;
	}

	public static function generateNewPassword($email=false)
	{
		if (!$email)
			return false;

		// Get user
		if ($userid = self::getUserByField($email, "email"))
		{
			$newPassword = \Tools::generateRandomString();

			$user = new User($userid);
			$user->password = User::generatePassword($newPassword);
			$user->store();

			$headers = "From: ".\Config::getCONFIG()->get("system_email")."\r\n";

			$message = "Hello ".$user->getFullName().",\n\n";
			$message.= "You have requested a new password for " . \Config::getCONFIG()->get("system_title").".\n";
			$message.= "You can now login with the following credentials:\n\n";
			$message.= "Username: ".$user->username."\n";
			$message.= "Password: ".$newPassword."\n\n";
			$message.= \Config::getCONFIG()->get("system_title");

			if (mail($user->email, "New password: ".\Config::getCONFIG()->get("system_title"), $message, $headers))
				return true;
			else
				return -1;
		}
		else
			return false;
	}

	public static function getCurrentSystem()
	{
		return \eve\model\IGB::getIGB()->getSolarsystemID();
	}

	/**
	 * Get selected chain id
	 * @return integer
	 */
	public static function getSelectedChain()
	{
		if (!isset($_SESSION["CURRENT_SELECTED_CHAIN"]) || $_SESSION["CURRENT_SELECTED_CHAIN"] == 0)
		{
			// We zitten in een systeem. Selecteer automatisch de chain die hier bij hoort.
			if (self::getCurrentSystem())
			{
				// Check eerst homesystems
				foreach (self::getUSER()->getAvailibleChains() as $chain)
				{
					if ($chain->homesystemID == self::getCurrentSystem())
					{
						self::setSelectedChain($chain->id);
						return $_SESSION["CURRENT_SELECTED_CHAIN"];
					}
				}

				// Check andere wormholes in de chain
				foreach (self::getUSER()->getAvailibleChains() as $chain)
				{
					foreach ($chain->getWormholes() as $wormhole)
					{
						if ($wormhole->solarSystemID == self::getCurrentSystem())
						{
							self::setSelectedChain($chain->id);
							return $_SESSION["CURRENT_SELECTED_CHAIN"];
						}
					}
				}
			}

			// Selecteer gewoon de eerste chain
			foreach (self::getUSER()->getAvailibleChains() as $chain)
			{
				self::setSelectedChain($chain->id);
				return $_SESSION["CURRENT_SELECTED_CHAIN"];
			}
		}

		return (isset($_SESSION["CURRENT_SELECTED_CHAIN"]))?$_SESSION["CURRENT_SELECTED_CHAIN"]:0;
	}

	public static function setSelectedChain($chainID)
	{
		\AppRoot::debug("setSelectedChain($chainID)");

		// Check of de user deze chain wel mag zien
		$allowedChain = false;
		if (\User::getUSER()->getIsSysAdmin())
			$allowedChain = true;
		else
		{
			foreach (\User::getUSER()->getAvailibleChains() as $chain) {
				if ($chainID == $chain->id) {
					$allowedChain = true;
					break;
				}
			}
		}

        if ($allowedChain)
        {
            $_SESSION["CURRENT_SELECTED_CHAIN"] = $chainID;
            return $_SESSION["CURRENT_SELECTED_CHAIN"];
        }

        // Reset
		\AppRoot::debug("Chain not available!!");
		self::getSelectedChain();
		return true;
	}

	public static function unsetSelectedChain()
	{
		unset($_SESSION["CURRENT_SELECTED_CHAIN"]);
	}

	public static function getSelectedSystem()
	{
		if (!isset($_SESSION["CURRENT_SELECTED_SYSTEM"]))
		{
			$system = self::getCurrentSystem();
			if ($system-0 == 0)
			{
				$system = 30000142;

				// Haal homesystem uit de chain.
				if ($result = \MySQL::getDB()->getRow("SELECT * FROM mapwormholechains WHERE id = ?", array(self::getSelectedChain())))
				{
					if ($result["homesystemid"] > 0)
						$system = $result["homesystemid"];
				}
			}

			self::setSelectedSystem($system);
		}

		return $_SESSION["CURRENT_SELECTED_SYSTEM"];
	}

	public static function setSelectedSystem($systemID="current")
	{
		if ($systemID == "current" && \eve\model\IGB::getIGB()->isIGB())
			$systemID = \eve\model\IGB::getIGB()->getSolarsystemID();

		$_SESSION["CURRENT_SELECTED_SYSTEM"] = $systemID;
	}

	public static function unsetSelectedSystem()
	{
		unset($_SESSION["CURRENT_SELECTED_SYSTEM"]);
	}

	public static function clearCharacters($userid)
	{
		$db = \MySQL::getDB();
		$db->update("characters", array("userid" => 0), array("userid" => $userid));
	}

	public static function clearCharactersByAPIID($apiKeyID)
	{
		$db = \MySQL::getDB();
		$db->update("characters", array("api_keyid" => 0), array("api_keyid" => $apiKeyID));
	}
}
?>