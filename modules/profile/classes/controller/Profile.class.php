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

			if (\Tools::POST("save"))
			{
				\User::getUSER()->username = \Tools::POST("username");
				\User::getUSER()->email = \Tools::POST("email");

                // Password
				if (\Tools::POST("password1") || \Tools::POST("password2")) {
					if (\Tools::POST("password1") == \Tools::POST("password2"))
						\User::getUSER()->password = \User::generatePassword(\Tools::POST("password1"));
					else
						$errors[] = "Passwords did not match";
				}

                // Settings
                foreach ($_POST["setting"] as $id => $value) {
                    $setting = \users\model\Setting::findById($id);
                    \User::getUSER()->setSetting($setting, $value);
                }


                \User::getUSER()->store();
				\AppRoot::refresh();
			}

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("user", \User::getUSER());
			$tpl->assign("settings", \users\model\Setting::findAll());
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
									"director"	=> $char->isDirector,
									"hasState"  => $char->hasState);
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