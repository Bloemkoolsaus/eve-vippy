<?php
class User
{
    /** @var \users\model\User|null */
	private static $loggedInUser = null;


	/**
	 * Get current logged in user object
	 * @return \users\model\User|null
	 */
	public static function getUSER()
	{
        if (!self::$loggedInUser) {
            if (isset($_SESSION["vippy"]["user"]["id"]) && $_SESSION["vippy"]["user"]["id"])
                self::$loggedInUser = new \users\model\User($_SESSION["vippy"]["user"]["id"]);
        }
        return self::$loggedInUser;
	}

    /**
     * Set logged in user
     * @param \users\model\User $user
     */
	public static function setUSER(\users\model\User $user=null)
	{
        $_SESSION["vippy"]["user"]["id"] = ($user)?$user->id:null;
        self::$loggedInUser = $user;
	}

    public static function unsetUser()
    {
        $_SESSION["vippy"]["user"]["id"] = null;
        self::$loggedInUser = null;
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
}