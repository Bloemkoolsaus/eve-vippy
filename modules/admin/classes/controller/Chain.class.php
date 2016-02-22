<?php
namespace admin\controller
{
	class Chain extends \scanning\model\Chain
	{
		public function getEditForm()
		{
		}

		public function getEditCorporations()
		{
			$ids = array();
			foreach (explode(",",\Tools::REQUEST("corporationids")) as $id)
			{
				$id = str_replace("[","",$id);
				$id = str_replace("]","",$id);
				if (strlen(trim($id)) > 0 && is_numeric($id) && $id != \Tools::REQUEST("remove"))
					$ids[] = $id;
			}

			if (\Tools::REQUEST("add"))
			{
				if (strlen(trim(\Tools::REQUEST("add"))) > 0 && is_numeric(\Tools::REQUEST("add")))
					$ids[] = \Tools::REQUEST("add");
			}

			$corporations = array();
			if ($results = \MySQL::getDB()->getRows("SELECT	c.id, c.name, a.name AS alliance
													FROM	corporations c
														LEFT JOIN alliances a ON a.id = c.allianceid
													WHERE	c.id IN (".implode(",",$ids).")
													ORDER BY a.name, c.name"))
			{
				foreach ($results as $result)
				{
					$corporations[] = array("id"		=> $result["id"],
											"name"		=> $result["name"],
											"alliance"	=> $result["alliance"]);
				}
			}
			return json_encode($corporations,true);
		}

		public function getEditAlliances()
		{
			$ids = array();
			foreach (explode(",",\Tools::REQUEST("allianceids")) as $id)
			{
				$id = str_replace("[","",$id);
				$id = str_replace("]","",$id);
				if (strlen(trim($id)) > 0 && is_numeric($id) && $id != \Tools::REQUEST("remove"))
					$ids[] = $id;
			}

			if (\Tools::REQUEST("add"))
			{
				if (strlen(trim(\Tools::REQUEST("add"))) > 0 && is_numeric(\Tools::REQUEST("add")))
					$ids[] = \Tools::REQUEST("add");
			}

			$corporations = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM alliances WHERE id IN (".implode(",",$ids).")"))
			{
				foreach ($results as $result)
				{
					$corporations[] = array("id"	=> $result["id"],
											"name"	=> $result["name"]);
				}
			}
			return json_encode($corporations,true);
		}
	}
}
?>