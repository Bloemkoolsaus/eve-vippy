<?php
namespace profile\view;

class Characters
{
    function getOverview($arguments=[])
    {
        \AppRoot::redirect("profile/account");
    }

    function getAddnew($arguments=[])
    {
        $crest = new \sso\Login();
        $crest->loginSSO("profile/characters");
    }
}