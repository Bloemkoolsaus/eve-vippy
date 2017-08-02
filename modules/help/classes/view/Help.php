<?php
namespace help\view;

class Help
{
    function getOverview($arguments=[])
    {
        \AppRoot::redirect("help/map/".implode("/",$arguments));
    }
}