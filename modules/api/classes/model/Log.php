<?php
namespace api\model;

class Log extends \Model
{
    protected $_table = "vippy_api_log";

    public $id = 0;
    public $apikeyID;
    public $logdate;
    public $url;
    public $ipAddress;
    public $info;
}