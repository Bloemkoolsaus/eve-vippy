<?php
namespace users\model\settings;

class Whtypefield extends \users\model\Setting
{
    function getEditForm($value=null)
    {
        $options = [
            ["name" => "Text field", "value" => "text"],
            ["name" => "Dropdown box", "value" => "select"]
        ];

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("name", "setting[".$this->id."]");
        $tpl->assign("options", $options);
        $tpl->assign("value", $value);
        return $tpl->fetch("elements/select");
    }
}