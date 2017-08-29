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
            $userID = \Session::getSession()->get(["user","id"]);
            \AppRoot::debug("getUSER(): ".($userID)?:null);
            if ($userID)
                self::$loggedInUser = new \users\model\User($userID);
        }
        if (!self::$loggedInUser) {
            \AppRoot::debug("No user, getUSER by cookie");
            if (\Tools::COOKIE("vippy"))
           		\users\model\User::loginByKey(\Tools::COOKIE("vippy"));
        }
        return self::$loggedInUser;
	}

    /**
     * Set logged in user
     * @param \users\model\User $user
     */
	public static function setUSER(\users\model\User $user=null)
	{
	    if ($user) {
            \Session::getSession()->set(["user","id"], $user->id);
            self::$loggedInUser = $user;
        } else {
	        self::unsetUser();
            self::$loggedInUser = null;
        }
	}

    public static function unsetUser()
    {
        \AppRoot::doCliOutput("UNSET USER");
        \AppRoot::log(\AppRoot::getStackTrace(), "unset-user");
        \Session::getSession()->remove(["user","id"]);
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