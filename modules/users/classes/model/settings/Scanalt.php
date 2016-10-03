<?php
namespace users\model\settings;

class Scanalt extends \users\model\Setting
{
    function getEditForm($value=null)
    {
        $options = [];
        foreach (\User::getUSER()->getAuthorizedCharacters() as $char) {
            $options[] = ["name" => $char->name, "value" => $char->id];
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("name", "setting[".$this->id."]");
        $tpl->assign("options", $options);
        $tpl->assign("value", $value);
        return $tpl->fetch("elements/select");
    }
}