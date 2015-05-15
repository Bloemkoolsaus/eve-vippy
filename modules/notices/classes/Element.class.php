<?php
namespace notices\elements\TypeElement
{
	class Element extends \SelectElement\Element
	{
		function __construct($title, $field, $dbfield=false)
		{
			parent::__construct($title, $field, $dbfield);
			$this->table = "notice_types";
			$this->namefield = "name";
			$this->keyfield = "id";
		}

		function getValue()
		{
			$this->value = parent::getValue();

			switch ($this->value)
			{
				case "message":
					$img = "info";
					break;
				case "warning":
					$img = "alert";
					break;
				case "error":
					$img = "error";
					break;
				default:
					$img = "info";
					break;
			}

			$value = "<img src='images/default/".$img.".png' style='height: 12px;' align='left'/> &nbsp; ".ucfirst($this->value);
			$value = "<div style='white-space: nowrap;'>".$value."</div>";
			return $value;
		}

		function getAddValue()
		{
			return \Tools::POST($this->field);
		}
	}
}

namespace notices\elements\AuthGroup
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

		function getValue()
		{
			$value = parent::getValue();
			$value = "<div style='white-space: nowrap;'>".$value."</div>";
			return $value;
		}
	}
}
?>