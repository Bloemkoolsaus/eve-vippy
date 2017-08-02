<?php
namespace map\view;

class Connection
{
    function getOverview($arguments=[])
    {
        return "connection-overview";
    }

    function getDetails($arguments=[])
    {
        $connection = new \map\model\Connection(array_shift($arguments));

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("connection", $connection);
        $tpl->assign("adddate", \Tools::getAge($connection->addDate));
        $tpl->assign("updatedate", \Tools::getAge($connection->updateDate));
        $tpl->assign("fromtype", \scanning\Connection::getWHTypeNameById($connection->fromWHTypeID));
        $tpl->assign("totype", \scanning\Connection::getWHTypeNameById($connection->toWHTypeID));
        return $tpl->fetch("map/connection/connection");
    }

    function getEdit($arguments=[])
    {
        $connection = new \map\model\Connection(array_shift($arguments));

        if (\Tools::POST("connectionid"))
        {
            $connection->eol = (\Tools::REQUEST("eol"))?true:false;
            $connection->mass = (\Tools::REQUEST("mass"))?\Tools::REQUEST("mass"):false;
            $connection->normalgates = (\Tools::REQUEST("normalgates"))?true:false;
            $connection->frigateHole = (\Tools::REQUEST("frigatehole"))?true:false;
            $connection->fromWHTypeID = (\Tools::REQUEST("fromtype"))?\Tools::REQUEST("fromtype"):false;
            $connection->toWHTypeID = (\Tools::REQUEST("totype"))?\Tools::REQUEST("totype"):false;
            $connection->store();
            \AppRoot::redidrectToReferer();
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
        return $tpl->fetch("map/connection/edit");
    }

    function getJumplog($arguments=[])
    {
        $jumplog = array();
        $connection = new \scanning\model\Connection(array_shift($arguments));

        if ($results = \MySQL::getDB()->getRows("SELECT t.typename, g.groupid, g.groupname, t.mass, l.jumptime, l.characterid
                                                FROM 	mapwormholejumplog l
                                                    INNER JOIN ".\eve\Module::eveDB().".invtypes t ON t.typeid = l.shipid
                                                    INNER JOIN ".\eve\Module::eveDB().".invgroups g ON g.groupid = t.groupid
                                                WHERE 	l.connectionid = ?
                                                ORDER BY g.groupname, t.typename"
                                    , [$connection->id]))
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
        return $tpl->fetch("map/connection/jumplog");
    }

    function getMass($arguments=[])
    {
        $connection = new \map\model\Connection(array_shift($arguments));

        if (isset($_POST["ship"])) {
            foreach ($_POST["ship"] as $key => $val)
            {
                $shipTypeID = null;
                if (substr($val, 0, 5) == "group") {
                    $id = str_replace("group","",$val);
                    $group = new \eve\model\ShipType($id);
                    $shipTypeID = $group->getShipBasedOnAvarageMass()->id;
                } else
                    $shipTypeID = str_replace("ship","",$val);

                if (is_numeric($shipTypeID) && $shipTypeID > 0) {
                    for ($i = 0; $i < $_POST["amount"][$key]; $i++) {
                        $connection->addJump($shipTypeID);
                    }
                }
            }
        }

        if (isset($_POST["manual"]))
        {
            if (strlen(trim($_POST["manual"]["mass"])) > 0 && $_POST["manual"]["mass"] > 0) {
                for ($i=0; $i<$_POST["manual"]["amount"]; $i++) {
                    $connection->addMass($_POST["manual"]["mass"]*1000000);
                }
            }
        }

        \AppRoot::redidrectToReferer();
    }

    function getDelete($arguments=[])
    {
        $connection = new \map\model\Connection(array_shift($arguments));
        $connection->delete();
        \AppRoot::redidrectToReferer();
    }
}