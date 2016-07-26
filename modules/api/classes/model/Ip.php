<?php
namespace api\model;

class Ip extends \Model
{
    protected $_table = "vippy_api_ips";

    public $apiKeyID;
    public $ipAddress;
}