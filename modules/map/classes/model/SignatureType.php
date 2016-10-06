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
}