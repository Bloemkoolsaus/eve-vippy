<?php
namespace notices\controller
{
	class Notice
	{
		function getOverviewSection()
		{
			require_once("modules/notices/classes/Element.class.php");
			$section = new \Section("notices", "id");

			$section->addElement("Type", "typeid", false, '\notices\elements\TypeElement');
			$section->addElement("SolarSystem", "solarsystemid", false, '\eve\elements\SolarSystem');
			$section->addElement("Title", "title");
			$section->addElement("Details", "body");
			$section->addElement("Persistant", "persistant", false, "BooleanElement");

			if (\User::getUSER()->hasRight("admin","sysadmin"))
				$section->addElement("AuthGroup", "authgroupid", false, '\notices\elements\AuthGroup');

			$expires = $section->addElement("Expires", "expiredate", false, "DateElement");
			$expires->fullView = true;
			$expires->setValue(date("Y-m-d",mktime(0,0,0,date("m")+1,date("d"),date("Y"))));

			if (\Tools::POST("id"))
			{
				$userid = $section->addElement("userid", "userid");
				$userid->setValue(\User::getUSER()->id);

				$deleted = $section->addElement("deleted", "deleted", false, "BooleanElement");
				$deleted->setValue(false);
			}

			$section->allowEdit = true;
			$section->allowDelete = true;
			$section->deletedfield = "deleted";

			if (!\User::getUSER()->hasRight("admin","sysadmin"))
				$section->whereQuery = " WHERE authgroupid IN (".implode(",",\User::getUSER()->getAuthGroupsIDs()).") ";

			return $section;
		}

		function getNewForm()
		{
			$errors = array();
			$solarSystem = new \eve\model\SolarSystem(\Tools::REQUEST("systemid"));

			if (\Tools::POST("addnotice"))
			{
				if (\Tools::POST("system"))
				{
					$solarSystemController = new \eve\controller\SolarSystem();
					if (!$solarSystem = $solarSystemController->getSolarsystemByName(\Tools::POST("system")))
						$errors[] = "Solarsystem `".\Tools::POST("system")."` not found.";
				}

				if (count($errors) == 0)
				{
					$notice = new \notices\model\Notice();
					$notice->solarSystemID = $solarSystem->id;
					$notice->typeID = (\Tools::POST("type")) ? \Tools::POST("type") : 1;
					$notice->title = \Tools::POST("title");
					$notice->body = \Tools::POST("body");
					$notice->messageDate = date("Y-m-d", strtotime(\Tools::POST("expdate")));
					$notice->persistant = true;
					$notice->authGroupID = \User::getUSER()->getCurrentAuthGroupID();
					$notice->store();

					if (\Tools::POST("redirect"))
						\AppRoot::redirect("index.php?module=".\Tools::POST("redirect")."&section=overview");
				}
			}

			$types = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM notice_types ORDER BY id"))
			{
				foreach ($results as $result) {
					$types[] = array("id" => $result["id"], "name" => ucfirst($result["name"]));
				}
			}

			$tpl = \SmartyTools::getSmarty();

			if ($solarSystem)
			{
				$tpl->assign("systemid", $solarSystem->id);
				$tpl->assign("systemname", $solarSystem->name);
			}

			$tpl->assign("types", $types);
			$tpl->assign("redirect", (\Tools::REQUEST("redirect")) ? \Tools::REQUEST("redirect") : "");
			$tpl->assign("expdate", date("Y-m-d", mktime(0,0,0,date("m")+2,0,date("Y"))));

			if (count($errors) > 0)
				$tpl->assign("errors", $errors);

			return $tpl->fetch(\SmartyTools::getTemplateDir("notices")."new.html");
		}
	}
}
?>