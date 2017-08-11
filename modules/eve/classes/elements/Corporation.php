<?php
namespace eve\elements;

class Corporation extends \AutoCompleteElement\Element
{
    public $keyfield = "id";
    public $namefield = "name";
    public $table = "corporations";

    function getEditHTML($extraAttributes=[])
    {
        $extraAttributes = array_merge($extraAttributes, ["element" => "eve-elements-Corporation"]);
        return parent::getEditHTML($extraAttributes);
    }

    public static function getValues()
    {
        $query = [];
        $searchMinLength = \Tools::Escape(\Tools::REQUEST("minsearchlen"))-0;
        if (\Tools::Escape(\Tools::REQUEST("term"))) {
            foreach (explode(" ", \Tools::Escape(\Tools::REQUEST("term"))) as $term) {
                if (strlen(trim($term)) >= $searchMinLength)
                    $query[] = "name LIKE '%".\MySQL::escape($term)."%'";
            }
        }

        $results = [];
        if (count($query) > 0) {
            $records = \MySQL::getDB()->getRows("SELECT * FROM corporations WHERE ".implode(" AND ", $query)." ORDER BY name");
            foreach ($records as $record) {
                $results[] = ["id" => $record[0], "label" => $record[1]];
            }
        }
        return json_encode($results);
    }
}