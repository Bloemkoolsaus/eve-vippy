<?php
namespace profile\controller
{
	class Profile
	{
		function getApiOverview()
		{
			$errors = array();
			$tpl = \SmartyTools::getSmarty();

			if (\Tools::POST("save"))
			{
				\AppRoot::setMaxExecTime(360);

				// Character van deze user afhalen.
				\AppRoot::debug("ClearCharacters()");
				\User::clearCharacters(\User::getUSER()->id);

				// Oude API keys opruimen.
				\AppRoot::debug("ClearApiKeys()");
				\User::getUSER()->deleteApiKeys();

				// Nieuwe keys toevoegen.
				if (isset($_POST["api"]))
				{
					foreach ($_POST["api"] as $i => $key)
					{
						\AppRoot::debug("Add new Key: ".$key["keyid"]);
						$api = new \eve\model\API($key["keyid"]);
						$api->keyID = $key["keyid"];
						$api->vCode = $key["vcode"];
						$api->userID = \User::getUSER()->id;
						$api->deleted = false;
						$api->store();
					}
				}

				$tpl->assign("saved",1);
			}

			if (count($errors) > 0)
				$tpl->assign("errors", $errors);

			$tpl->assign("user", \User::getUSER());
			return $tpl->fetch(\SmartyTools::getTemplateDir("profile")."api.html");
		}

		function getAccountSettings()
		{
			$errors = array();
			$settings = array();

			if ($results = \MySQL::getDB()->getRows("SELECT s.id, s.title AS name, u.value
													FROM    user_settings s
													    LEFT JOIN user_user_settings u ON u.settingid = s.id
													    	AND u.userid = ?"
										, array(\User::getUSER()->id)))
			{
				foreach ($results as $result)
				{
					$settings[] = array("id"	=> $result["id"],
										"name"	=> $result["name"],
										"value"	=> $result["value"]);
				}
			}

			if (\Tools::POST("save"))
			{
				\User::getUSER()->username = \Tools::POST("username");
				\User::getUSER()->email = \Tools::POST("email");

				if (\Tools::POST("password1") || \Tools::POST("password2"))
				{
					if (\Tools::POST("password1") == \Tools::POST("password2"))
						\User::getUSER()->password = \User::generatePassword(\Tools::POST("password1"));
					else
						$errors[] = "Passwords did not match";
				}
				\User::getUSER()->store();

				foreach ($settings as $setting)
				{
					\MySQL::getDB()->updateinsert("user_user_settings",
											array(	"settingid" => $setting["id"],
													"userid"	=> \User::getUSER()->id,
													"value"		=> \Tools::POST("setting".$setting["id"])),
											array(	"settingid" => $setting["id"],
													"userid"	=> \User::getUSER()->id));
				}
				\AppRoot::refresh();
			}

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("user", \User::getUSER());
			$tpl->assign("settings", $settings);
			$tpl->assign("errors", $errors);
			return $tpl->fetch(\SmartyTools::getTemplateDir("profile")."accountsettings.html");
		}

		function getCharacterOverview()
		{
			if (\Tools::REQUEST("setmain")) {
				\User::getUSER()->setMainCharacter(\Tools::REQUEST("setmain"));
				\AppRoot::redirect("index.php?module=profile&section=chars");
			}

			$characters = array();
			foreach ($characters as $char)
			{
				$character = array(	"id"	=> $char->id,
									"name"	=> $char->name,
									"corporationid"	=> $char->getCorporation()->id,
									"corporation"	=> $char->getCorporation()->name,
									"allianceid"	=> $char->getCorporation()->getAlliance()->id,
									"alliance"		=> $char->getCorporation()->getAlliance()->name,
									"ceo"		=> $char->isCEO,
									"director"	=> $char->isDirector);
				if (\User::getUSER()->getMainCharacter()->id == $char->id)
					$character["main"] = 1;

				$characters[] = $character;
			}

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("user", \User::getUSER());
			$tpl->assign("characters", \User::getUSER()->getCharacters());
			return $tpl->fetch(\SmartyTools::getTemplateDir("profile")."charoverview.html");
		}
	}
}
?>