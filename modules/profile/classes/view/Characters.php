<?php
namespace profile\view;

class Characters
{
    function getOverview($arguments=[])
    {
        $characters = \User::getUSER()->getCharacters();

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("characters", $characters);
        return $tpl->fetch("profile/characters/overview");
    }

    function getAddnew($arguments=[])
    {
        $oauth = new \api\model\Oauth();
        $oauth->requestAuthorization();
    }
}