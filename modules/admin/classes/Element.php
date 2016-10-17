<?php
namespace admin\elements\AuthGroup
{
	class Element extends \SelectElement\Element
	{
		function __construct($title, $field, $dbfield=false)
		{
			parent::__construct($title, $field, $dbfield);
			$this->table = "user_auth_groups";
			$this->namefield = "name";
			$this->keyfield = "id";
		}
	}
}
?>