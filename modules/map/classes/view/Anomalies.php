<?php
namespace map\view;

class Anomalies
{
    function getClear($arguments=[])
    {
        $map = \map\model\Map::findByName(array_shift($arguments));
        $system = \map\model\System::getSolarsystemByName(array_shift($arguments));

    }
}