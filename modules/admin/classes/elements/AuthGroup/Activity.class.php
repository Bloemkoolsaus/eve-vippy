<?php
namespace admin\elements\AuthGroup
{
	class Activity extends \TextElement\Element
	{
		function getValue()
		{
			if ($results = \MySQL::getDB()->getRows("SELECT lastmapupdatedate
													FROM 	mapwormholechains
													WHERE 	authgroupid = ?
													ORDER BY lastmapupdatedate DESC"
										, array($this->value)))
			{
				foreach ($results as $result)
				{
					if (strlen(trim($result["lastmapupdatedate"])) > 0)
						return \Tools::getAge($result["lastmapupdatedate"]);
				}
			}

			return null;
		}
	}
}
?>