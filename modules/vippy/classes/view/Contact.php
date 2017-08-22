<?php
namespace vippy\view;

class Contact
{
    function getOverview($arguments=[])
    {
        \AppRoot::title("Contact");
        \User::setUSER(null);
        $tpl = \SmartyTools::getSmarty();
        return $tpl->fetch("vippy/contact");
    }
}