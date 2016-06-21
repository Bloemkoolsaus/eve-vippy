<?php
namespace scanning\controller
{
	class Connection
	{
		function getEditForm($fromID, $toID)
		{
			\AppRoot::debug("getEditForm");

			$connection = null;
			if (\Tools::REQUEST("connectionid"))
				$connection = new \scanning\model\Connection(\Tools::REQUEST("connectionid"));
			else
				$connection = \scanning\model\Connection::getConnectionByWormhole($fromID, $toID, \User::getSelectedChain());

		}

		function getDetailsPopup($connectionID)
		{
			$connection = new \scanning\model\Connection();
			$connection->loadById($connectionID);

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("connection", $connection);

			$tpl->assign("adddate", \Tools::getAge($connection->addDate));
			$tpl->assign("updatedate", \Tools::getAge($connection->updateDate));

			$tpl->assign("fromtype", \scanning\Connection::getWHTypeNameById($connection->fromWHTypeID));
			$tpl->assign("totype", \scanning\Connection::getWHTypeNameById($connection->toWHTypeID));

			return $tpl->fetch(\SmartyTools::getTemplateDir("scanning")."connection/details.html");
		}

		function getJumplogSummary($connectionID)
		{
			$jumplog = array();
			$connection = new \scanning\model\Connection();
			$connection->loadById($connectionID);

            if ($results = \MySQL::getDB()->getRows("SELECT t.typename, g.groupid, g.groupname, t.mass, l.jumptime, l.characterid
													FROM 	mapwormholejumplog l
														INNER JOIN ".\eve\Module::eveDB().".invtypes t ON t.typeid = l.shipid
														INNER JOIN ".\eve\Module::eveDB().".invgroups g ON g.groupid = t.groupid
													WHERE 	l.connectionid = ?
													ORDER BY g.groupname, t.typename"
                , array($connection->id)))
            {
                foreach ($results as $result)
                {
                    $key = "beforemass";
                    if ($connection->massUpdateDate !== null) {
                        if (strtotime($result["jumptime"]) > strtotime($connection->massUpdateDate))
                            $key = "aftermass";
                    }

                    if (!isset($jumplog[$key][$result["groupid"]]))
                    {
                        $jumplog[$key][$result["groupid"]] = array(	"class" 	=> $result["groupname"],
                                                                       "amount" 	=> 0,
                                                                       "manual" 	=> 0,
                                                                       "mass" 		=> 0);
                    }
                    $jumplog[$key][$result["groupid"]]["mass"] += $result["mass"];
                    $jumplog[$key][$result["groupid"]]["amount"] += 1;
                    if ($result["characterid"] == null)
                        $jumplog[$key][$result["groupid"]]["manual"] += 1;

                }
            }

            /**
             * Manual added mass
             */
            if ($results = \MySQL::getDB()->getRows("SELECT *
                                                    FROM    mapwormholejumplog
                                                    WHERE   shipid IS NULL
                                                    AND     connectionid = ?"
                                        , array($connection->id)))
            {
                foreach ($results as $result)
                {
                    $key = "beforemass";
                    if ($connection->massUpdateDate !== null) {
                        if (strtotime($result["jumptime"]) > strtotime($connection->massUpdateDate))
                            $key = "aftermass";
                    }

                    if (!isset($jumplog[$key][0]))
                    {
                        $jumplog[$key][0] = array("class"   => "none",
                                                  "amount" 	=> 0,
                                                  "manual" 	=> 0,
                                                  "mass"	=> 0);
                    }
                    $jumplog[$key][0]["mass"] += $result["mass"];
                    if ($result["characterid"] == null)
                        $jumplog[$key][0]["manual"] += 1;

                }
            }

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("connection", $connection);
			$tpl->assign("jumplog", $jumplog);
			return $tpl->fetch(\SmartyTools::getTemplateDir("scanning")."connection/jumplog.summary.html");
		}
	}
}
?>