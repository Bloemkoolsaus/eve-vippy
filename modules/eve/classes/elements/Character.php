<?php
namespace eve\elements;

class Character extends \AutoCompleteElement\Element
{
    public $keyfield = "id";
    public $namefield = "name";
    public $table = "characters";

    function getEditHTML($extraAttributes=array())
    {
        $extraAttributes = array_merge($extraAttributes, array("element" => "eve-elements-Character"));
        return parent::getEditHTML($extraAttributes);
    }

    public static function getValues()
    {
        $query = array();

        $searchMinLength = \Tools::Escape(\Tools::REQUEST("minsearchlen"))-0;
        if (\Tools::Escape(\Tools::REQUEST("term")))
        {
            foreach (explode(" ", \Tools::Escape(\Tools::REQUEST("term"))) as $term) {
                if (strlen(trim($term)) >= $searchMinLength)
                    $query[] = "name LIKE '%".\MySQL::escape($term)."%'";
            }
        }

        $results = array();
        if (count($query) > 0)
        {
            $records = \MySQL::getDB()->getRows("SELECT *
                                                FROM	characters
                                                WHERE	".implode(" AND ", $query)."
                                                ORDER BY name");
            foreach ($records as $record) {
                $char = new \eve\model\Character();
                $char->load($record);
                $results[] = [
                    "id" => $char->id,
                    "label" => "[".$char->getCorporation()->ticker."] ".$char->name
                ];
            }
        }
        return json_encode($results);
    }

    function getValue()
    {
        $character = new \eve\model\Character($this->value);
        return $character->name;
    }
}