<?php
namespace help\view;

class Contact
{
    function getOverview($arguments=[])
    {
        \AppRoot::title("Help - Contact");
        $tpl = \SmartyTools::getSmarty();
        return $tpl->fetch("help/contact");
    }
}