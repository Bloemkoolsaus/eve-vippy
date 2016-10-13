<?php
namespace notices\view;

class Notes
{
    function getOverview($arguments=[])
    {
        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("notices", \notices\model\Notice::findAll(["authgroupid" => \User::getUSER()->getCurrentAuthGroupID()], ["messagedate"]));
        return $tpl->fetch("notices/overview");
    }

    function getNew($arguments=[])
    {
        return $this->getEdit(["new"]);
    }

    function getEdit($arguments=[])
    {
        $errors = [];
        $notice = new \notices\model\Notice(array_shift($arguments));

        $types = [];
        $results = \MySQL::getDB()->getRows("select * from notice_types");
        foreach ($results as $result) {
            $types[] = $result;
        }

        if (\Tools::POST("title") || \Tools::POST("system"))
        {
            $solarSystem = null;
            if (!\Tools::POST("title"))
                $errors[] = "No title given";
            if (!\Tools::POST("system"))
                $errors[] = "No solarsystem given";
            else {
                $solarSystem = \eve\model\SolarSystem::getSolarsystemByName(\Tools::POST("system"));
                if (!$solarSystem)
                    $errors[] = "Solarsystem ".\Tools::POST("system")." not found";
            }

            if (count($errors) == 0)
            {
                if (!$notice)
                    $notice = new \notices\model\Notice();

                $notice->solarSystemID = $solarSystem->id;
                $notice->typeID = \Tools::POST("type");
                $notice->title = \Tools::POST("title");
                $notice->body = \Tools::POST("body");

                if (\Tools::POST("expiredate"))
                    $notice->expireDate = date("Y-m-d", strtotime(\Tools::POST("expiredate")));

                $notice->persistant = true;
                $notice->store();
                \AppRoot::redirect("notices/notes");
            }
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("types", $types);
        $tpl->assign("errors", $errors);
        $tpl->assign("notice", $notice);
        return $tpl->fetch("notices/edit");
    }

    function getDelete($arguments=[])
    {
        $notice = new \notices\model\Notice(array_shift($arguments));
        $notice->delete();
        \AppRoot::redirect("notices/notes");
    }
}