<?php
namespace profile\view;

class Profile
{
    function getOverview($arguments=[])
    {
        \AppRoot::redirect("profile/account");
    }
}