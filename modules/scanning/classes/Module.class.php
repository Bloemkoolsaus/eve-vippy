<?php
namespace scanning;

class Module extends \Module
{
    public $moduleName = "scanning";
    public $moduleTitle = "Scanning";

    function getContent()
    {
        \AppRoot::redirect("map");
    }
}