<?php
namespace eve\controller
{
	class Title
	{
		private $db = null;

		function __construct()
		{
			$this->db = \MySQL::getDB();
		}

		function getTitleManagement()
		{
			if (\Tools::POST("save"))
			{
				if ($results = $this->db->getRows("SELECT * FROM eve_characters_titles"))
				{
					foreach ($results as $result)
					{
						if (\Tools::POST("title".$result["id"]))
						{
							$this->db->updateinsert("eve_characters_titles_groups",
													array(	"titleid" => $result["id"],
															"groupid" => \Tools::POST("title".$result["id"])),
													array(	"titleid" => $result["id"]));
						}
					}
				}
			}

			$titles = array();
			if ($results = $this->db->getRows("	SELECT	t.id, t.title, corp.name as corpname, g.groupid
												FROM	eve_characters_titles t
													INNER JOIN eve_characters chr ON chr.id = t.characterid
												    INNER JOIN eve_corporations corp ON corp.id = chr.corporationid
    												LEFT JOIN eve_characters_titles_groups g ON g.titleid = t.id
												GROUP BY t.id
												ORDER BY corp.name, t.title"))
			{
				foreach ($results as $result)
				{
					$titles[] = array(	"id"	=> $result["id"],
										"corp"	=> $result["corpname"],
										"name"	=> $result["title"],
										"group"	=> $result["groupid"]);
				}
			}

			$groups = array();
			if ($results = $this->db->getRows("SELECT * FROM user_groups WHERE deleted = 0 ORDER BY name"))
			{
				foreach ($results as $result)
				{
					$groups[] = array("id" => $result["id"], "name" => $result["name"]);
				}
			}

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("titles", $titles);
			$tpl->assign("groups", $groups);
			return $tpl->fetch(\SmartyTools::getTemplateDir("eve")."titles.html");
		}
	}
}
?>