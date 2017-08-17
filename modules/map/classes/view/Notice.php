<?php
namespace map\view;

class Notice
{
    function getAdd($arguments=[])
    {
        $system = new \map\model\SolarSystem(array_shift($arguments));
        $map = new \map\model\Map(array_shift($arguments));

        $types = [];
        $results = \MySQL::getDB()->getRows("select * from notice_types");
        foreach ($results as $result) {
            $types[] = $result;
        }

        if (\Tools::POST("title"))
        {
            $notice = new \notices\model\Notice();
            $notice->authGroupID = $map->authgroupID;
            $notice->solarSystemID = $system->id;
            $notice->typeID = \Tools::POST("type");
            $notice->title = \Tools::POST("title");
            $notice->body = \Tools::POST("body");

            if (\Tools::POST("expiredate"))
                $notice->expireDate = date("Y-m-d", strtotime(\Tools::POST("expiredate")));

            $notice->persistant = true;
            $notice->store();
            \AppRoot::redirect("map/".$map->name);
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("system", $system);
        $tpl->assign("map", $map);
        $tpl->assign("types", $types);
        return $tpl->fetch("map/system/notice/add");
    }

    function getRemove($arguments=[])
    {
        $notice = new \notices\model\Notice(array_shift($arguments));
        $map = new \map\model\Map(array_shift($arguments));

        if (\Tools::POST("confirmed")) {
            $notice->delete();
            \AppRoot::redirect("map/".$map->getURL());
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("notice", $notice);
        $tpl->assign("map", $map);
        return $tpl->fetch("map/system/notice/remove");
    }

    function getDrifter($arguments=[])
    {
        $system = new \map\model\SolarSystem(array_shift($arguments));
        $map = new \map\model\Map(array_shift($arguments));

        $drifter = \notices\model\Drifter::findOne(["solarsystemid" => $system->id]);
        if (!$drifter) {
            $drifter = new \notices\model\Drifter();
            $drifter->solarSystemID = $system->id;
            $drifter->authGroupID = $map->authgroupID;
        }

        if (\Tools::POST("store") == "drifters") {
            $drifter->nrDrifters = (\Tools::POST("nrdrifters"))?:0;
            $drifter->comments = (\Tools::POST("comments"))?:null;
            if ($drifter->nrDrifters == 0) {
                $drifter->delete();
                return "Deleted";
            } else {
                $drifter->store();
                print_r($drifter);
                return "Stored";
            }
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("map", $map);
        $tpl->assign("system", $system);
        $tpl->assign("drifter", $drifter);
        return $tpl->fetch("map/system/notice/drifter");
    }
}