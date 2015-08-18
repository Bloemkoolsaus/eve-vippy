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

			if (\Tools::POST("addmass"))
			{
				foreach ($_POST["ship"] as $key => $val)
				{
					$shipTypeID = null;
					if (substr($val, 0, 5) == "group")
					{
						$id = str_replace("group","",$val);
						$group = new \eve\model\ShipType($id);
						$shipTypeID = $group->getShipBasedOnAvarageMass()->id;
					}
					else
						$shipTypeID = str_replace("ship","",$val);

                    if (is_numeric($shipTypeID) && $shipTypeID > 0)
                    {
                        for ($i = 0; $i < $_POST["amount"][$key]; $i++) {
                            $connection->addJump($shipTypeID);
                        }
                    }
				}

                if (isset($_POST["manual"]))
                {
                    if (strlen(trim($_POST["manual"]["mass"])) > 0 && $_POST["manual"]["mass"] > 0)
                    {
                        for ($i=0; $i<$_POST["manual"]["amount"]; $i++) {
                            $connection->addMass($_POST["manual"]["mass"]*1000000);
                        }
                    }
                }

				\AppRoot::redirect("index.php?module=scanning&section=overview");
			}

			if (\Tools::POST("delete"))
			{
				if ($connection != null)
					$connection->delete();

				\AppRoot::redirect("index.php?module=scanning&section=overview");
			}

			if (\Tools::POST("connectionid"))
			{
				$connection->fromSystemID = $fromID;
				$connection->toSystemID = $toID;
				$connection->chainID = \User::getSelectedChain();
				$connection->eol = (\Tools::REQUEST("eol"))?1:0;
				$connection->mass = (\Tools::REQUEST("mass"))?\Tools::REQUEST("mass"):0;
				$connection->normalgates = (\Tools::REQUEST("normalgates"))?1:0;
				$connection->frigateHole = (\Tools::REQUEST("frigatehole"))?1:0;
				$connection->fromWHTypeID = (\Tools::REQUEST("fromtype"))?\Tools::REQUEST("fromtype"):0;
				$connection->toWHTypeID = (\Tools::REQUEST("totype"))?\Tools::REQUEST("totype"):0;
				$connection->store();

				\AppRoot::redirect("index.php?module=scanning&section=overview");
			}

			$whtypes = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM mapwormholetypes ORDER BY name")) {
				foreach ($results as $result) {
					$whtypes[] = array("id" => $result["id"], "name" => $result["name"]);
				}
			}

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("connection", $connection);
			$tpl->assign("whtypes", $whtypes);
			$tpl->assign("ships", \eve\model\Ship::getShips());
			$tpl->assign("shiptypes", \eve\model\ShipType::getShipTypes());
			return $tpl->fetch("scanning/connection/edit");
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