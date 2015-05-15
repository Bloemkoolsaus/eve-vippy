<?php
namespace atlas\console
{
	class Atlas
	{
		function loginByAtlas()
		{
			if (\Tools::REQUEST("loginbyatlas"))
			{
				$loggedIn = false;
				$key = \Tools::REQUEST("loginbyatlas");

				$user = \users\model\User::getUserByKey($key);
				if ($user !== null)
				{
					// Check atlas login.
					$api = new \api\Client();
					$api->baseURL = \AppRoot::getDBConfig("atlas_url")."api/";

					$result = $api->get("profile/loggedin/".$key);

					if ($result["result"]["user"] !== null)
					{
						if ($result["result"]["user"]["ipaddress"] == $_SERVER["REMOTE_ADDR"])
						{
							$user->setLoginStatus();
							\AppRoot::redirect("index.php");
						}
					}
				}
			}

			return false;
		}
	}
}
?>