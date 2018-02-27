<?php
namespace esi\model;

class Log extends \Model
{
    protected $_keyfield = ["requestType", "url", "requestDate"];

    public $requestType = "get";
    public $url;
    public $requestDate;
    public $expireDate;
    public $httpStatus = 0;
    public $errorRemain;
    public $errorReset;
    public $content;
    public $response;
}