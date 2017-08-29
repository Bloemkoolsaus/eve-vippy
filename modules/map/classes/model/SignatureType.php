<?php
namespace map\model;

class SignatureType extends \Model
{
    protected $_table = "map_signature_type";

    public $id;
    public $name;
    public $description;


    /**
     * Mag een sig van dit type opgeruimd worden als die te oud is?
     * @return bool
     */
    function mayCleanup()
    {
        if ($this->name == "pos")
            return false;
        if ($this->name == "citadel")
            return false;

        return true;
    }

    /**
     * Find by ID
     * @param $id
     * @param null $class
     * @return \map\model\SignatureType|null
     */
    public static function findById($id, $class = null)
    {
        $sigType = \Cache::memory()->get(["signature-type", $id]);
        if (!$sigType) {
            $sigType = parent::findById($id, $class);
            \Cache::memory(0)->set(["signature-type", $id], $sigType);
        }
        return $sigType;
    }
}