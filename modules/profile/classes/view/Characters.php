<?php
namespace profile\view;

class Characters
{
    function getOverview($arguments=[])
    {
        \AppRoot::title("Profile");
        \AppRoot::title("Characters");

        $tpl = \SmartyTools::getSmarty();
        return $tpl->fetch("profile/characters/overview");
    }

    function getAddnew($arguments=[])
    {
        $crest = new \crest\Login();
        $crest->loginSSO("profile/characters");
    }
}