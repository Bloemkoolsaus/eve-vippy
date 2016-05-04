<?php
namespace eve\elements;

class Ship extends \AutoCompleteElement\Element
{
    public $showLogo = false;
    public $showType = false;

    function getValue()
    {
        $ship = new \eve\model\Ship($this->value);
        $html = $ship->name;

        if ($this->showType && $ship->getShipType() != $ship->name)
            $html .= " - <i>".$ship->getShipType()."</i>";

        if ($this->showLogo)
            $html .= "<img src='https://image.eveonline.com/Render/".$ship->id."_32.png' style='height: 20px; margin: -2px; margin-right: 6px; border-radius: 3px;' align='left'/>";

        return $html;
    }

    public static function getValues()
    {
        $query = ["g.categoryid = 6"];

        $searchMinLength = \Tools::Escape(\Tools::REQUEST("minsearchlen"))-0;
        if (\Tools::Escape(\Tools::REQUEST("term")))
        {
            foreach (explode(" ", \Tools::Escape(\Tools::REQUEST("term"))) as $term) {
                if (strlen(trim($term)) >= $searchMinLength)
                    $query[] = "typename LIKE '%".\MySQL::escape($term)."%'";
            }
        }

        $results = array();
        if (count($query) > 0)
        {
            if ($records = \MySQL::getDB()->getRows("
                                select i.*
                                from    " . \eve\Module::eveDB() . ".invtypes i
                                    inner join " . \eve\Module::eveDB() . ".invgroups g on g.groupid = i.groupid
                                where   " . implode(" and ", $query) . "
                                order by i.typename"))
            {
                foreach ($records as $record)
                {
                    $ship = new \eve\model\Ship();
                    $ship->load($record);

                    $results[] = [
                        "id" => $ship->id,
                        "label" => $ship->name . " (" . $ship->getShipType() . ")"
                    ];
                }
            }
        }
        return json_encode($results);
    }
}