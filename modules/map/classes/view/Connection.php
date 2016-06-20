<?php
namespace map\view
{
    class Connection
    {
        function getOverview($arguments=[])
        {
            return "connection-overview";
        }

        function getEdit($arguments=[])
        {
            $connection = new \map\model\Connection(array_shift($arguments));

            if (\Tools::POST("store"))
            {
                $connection->mass = \Tools::POST("mass");
                $connection->eol = (\Tools::POST("eol"))?true:false;
                $connection->frigateHole = (\Tools::POST("frigatehole"))?true:false;
                $connection->allowCapitals = (\Tools::POST("allowcapitals"))?true:false;
                $connection->normalgates = (\Tools::POST("normalgates"))?true:false;
                $connection->store();

                \AppRoot::redirect("/map/".$connection->getChain()->name."/");
            }

            $tpl = \SmartyTools::getSmarty();
            $tpl->assign("connection", $connection);
            return $tpl->fetch("map/connection/edit");
        }

        function getDetails($arguments=[])
        {
            $connection = new \map\model\Connection(array_shift($arguments));

            $tpl = \SmartyTools::getSmarty();
            $tpl->assign("connection", $connection);
            return $tpl->fetch("map/connection/details");
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
            return $tpl->fetch("map/connection/jumplog");
        }

        function getMass($arguments=[])
        {
            $connection = new \map\model\Connection(array_shift($arguments));

            if (\Tools::POST("addmass"))
            {
                for ($i=0; $i<\Tools::POST("jumps"); $i++) {
                    $connection->addMass(\Tools::POST("amount"));
                }
                \AppRoot::redirect("/map/".$connection->getChain()->name."/");
            }

            return "connection-mass";
        }

        function getDelete($arguments=[])
        {
            $connection = new \map\model\Connection(array_shift($arguments));
            $connection->delete();
            \AppRoot::redirect("/map/".$connection->getChain()->name."/");
        }
    }
}