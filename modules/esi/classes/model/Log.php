<?php
namespace esi\model;

class Log extends \Model
{
    public $id;
    public $requestType = "get";
    public $url;
    public $requestDate;
    public $expireDate;
    public $httpStatus = 0;
    public $content;
    public $response;
}