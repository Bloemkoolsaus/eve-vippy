<?php
namespace admin\view;

class Accesslist
{
    function getOverview($arguments=[])
    {
        if (!\User::getUSER()->isAdmin())
            \AppRoot::redirect("");

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("accesslists", \User::getUSER()->getAdminAccessLiss());
        return $tpl->fetch("admin/accesslists/overview");
    }

    function getNew($arguments=[])
    {
        if (!\User::getUSER()->isAdmin())
            \AppRoot::redirect("");

        if (\Tools::POST("title")) {
            $accesslist = new \admin\model\AccessList();
            $accesslist->ownerID = \User::getUSER()->id;
            $accesslist->title = \Tools::POST("title");
            $accesslist->store();
            \AppRoot::redirect("admin/accesslist/edit/".$accesslist->id);
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("accesslist", null);
        return $tpl->fetch("admin/accesslists/edit");
    }

    function getEdit($arguments=[])
    {
        if (!\User::getUSER()->isAdmin())
            \AppRoot::redirect("");

        $accessList = new \admin\model\AccessList(array_shift($arguments));
        if (!$accessList->canAdmin(\User::getUSER()->id))
            \AppRoot::redidrectToReferer();

        if (\Tools::REQUEST("deletealliance")) {
            $accessList->removeAlliance(\Tools::REQUEST("deletealliance"));
            $accessList->store();
            \AppRoot::redirect("admin/accesslist/edit/".$accessList->id);
        }

        if (\Tools::REQUEST("deletecorp")) {
            $accessList->removeCorporation(\Tools::REQUEST("deletecorp"));
            $accessList->store();
            \AppRoot::redirect("admin/accesslist/edit/".$accessList->id);
        }

        if (\Tools::REQUEST("deletechar")) {
            $accessList->removeCharacter(\Tools::REQUEST("deletechar"));
            $accessList->store();
            \AppRoot::redirect("admin/accesslist/edit/".$accessList->id);
        }

        if (\Tools::POST("title"))
        {
            $accessList->title = \Tools::POST("title");

            if (\Tools::POST("alliance")) {
                $alliance = new \eve\model\Alliance(\Tools::POST("alliance"));
                $accessList->addAlliance($alliance);
            }
            if (\Tools::POST("corporation")) {
                $corporation = new \eve\model\Corporation(\Tools::POST("corporation"));
                $accessList->addCorporation($corporation);
            }
            if (\Tools::POST("character")) {
                $character = new \eve\model\Character(\Tools::POST("character"));
                $accessList->addCharacter($character);
            }

            $accessList->store();
            \AppRoot::redirect("admin/accesslist/edit/".$accessList->id);
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("accesslist", $accessList);
        return $tpl->fetch("admin/accesslists/edit");
    }

    function getDelete($arguments=[])
    {
        if (!\User::getUSER()->isAdmin())
            \AppRoot::redirect("");

        $accessList = new \admin\model\AccessList(array_shift($arguments));
        if (!$accessList->canAdmin(\User::getUSER()->id))
            \AppRoot::redidrectToReferer();

        $accessList->delete();
        \AppRoot::redirect("admin/accesslist");
    }
}