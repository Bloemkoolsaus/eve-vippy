<?php
namespace map\model;

class AnomalyType extends \Model
{
    protected $_table = "map_anomaly_type";

    public $id;
    public $type;
    public $name;
}