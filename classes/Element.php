<?php
namespace TextElement
{
	class Element
	{
		public $title;
		public $field;
		public $dbfield;
		public $value;
		public $help;
		public $type = "text";

		public $searchable = true;
		public $obligatory = false;

		public $isNumber = false;
		public $isBoolean = false;

		public $inEdit = true;
		public $allowEdit = true;
		public $inOverview = true;

		public $templateFile = "default";
		public $errors = array();

		function __construct($title, $field, $dbfield=false)
		{
			$this->title = $title;
			$this->field = $field;
			if (!$dbfield)
				$this->dbfield = $field;
			else
				$this->dbfield = $dbfield;
		}

		function setValue($value)
		{
			if ($this->obligatory && strlen(trim($value)) == 0)
				return false;

			$this->value = $value;
			return true;
		}

		function getValue()
		{
			return $this->value;
		}

		function getAddValue()
		{
			return $this->getValue();
		}

		function getEditValue()
		{
			return $this->getValue();
		}

		function getEditHTML()
		{
			\AppRoot::debug("GetEditHTML(".$this->field.") ".$this->templateFile." : " . $this->value);
			$template = \SmartyTools::getSmarty();
			$template->assign("name", $this->field);
			$template->assign("value", $this->getEditValue());
			if (strlen(trim($this->help)) > 0)
				$template->assign("help", $this->help);
			else
				$template->assign("help", false);
			return $template->fetch("elements/".$this->templateFile);
		}

		function getErrors()
		{
			return $this->errors;
		}
	}
}

namespace TextareaElement
{
	class Element extends \TextElement\Element
	{
		public $templateFile = "textarea";

		function getValue($dbvalue=false)
		{
			$val = $this->value;
			if (strlen($val) > 80)
				$val = substr($val, 0, 80) . "....";
			return $val;
		}

		function getEditValue()
		{
			return $this->value;
		}
	}
}

namespace NumberElement
{
	class Element extends \TextElement\Element
	{
		public $isNumber = true;
		public $showNullAsEmpty = false;

		function setValue($value)
		{
			if ($this->obligatory && strlen(trim($value)) == 0)
				return false;

			$value = str_replace(",",".",$value);
			if (is_numeric($value)) {
				$this->value = $value;
				return true;
			} else
				return false;
		}

		function getValue($dbvalue=false)
		{
			$parts = explode(".",$this->value);
			if (count($parts) > 1)
				$decimals = strlen($parts[1]);
			else
				$decimals = 0;
			return number_format(($this->value), $decimals, ",", ".");
		}

		function getAddValue()
		{
			return $this->value;
		}

		function getEditValue()
		{
			$value = $this->getValue();
			if ($this->showNullAsEmpty && $value == 0)
				$value = "";

			return $value;
		}
	}
}

namespace BooleanElement
{
	class Element extends \TextElement\Element
	{
		public $isBoolean = true;
		public $templateFile = "boolean";

		function setValue($value)
		{
			if (is_numeric($value)) {
				if ($value == 0)
					$this->value = false;
				else
					$this->value = true;
			} else {
				if (!isset($value) || !$value || strlen(trim($value)) == 0)
					$this->value = false;
				else
					$this->value = true;
			}

			return true;
		}

		function getValue($dbvalue=false)
		{

			if ($this->value)
				return "<img src='images/default/apply.small.png' alt='Yes' /> &nbsp;Yes";
			else
				return "<img src='images/default/cross.small.png' alt='No' /> &nbsp;No &nbsp;";
		}

		function getAddValue()
		{
			return ($this->value) ? 1 : 0;
		}

		function getEditValue()
		{
			return ($this->value) ? "checked" : "";
		}
	}
}

namespace SelectElement
{
	class Element extends \TextElement\Element
	{
		public $keyfield = null;
		public $namefield = null;
		public $table = null;
		public $orderbyfield = false;
		public $deletedfield = false;
		public $templateFile = "select";
		public $whereQuery = "";
		public $type = "relation";

		private $options = array();

		function setValue($value)
		{
			if ($this->obligatory && strlen(trim($value)) == 0)
				return false;

			$this->value = $value;
			return true;
		}

		function getValue()
		{
			if (count($this->options) == 0)
				$this->loadFromDatabase();

			foreach($this->options as $option) {
				if ($option["value"] == $this->value) {
					return $option["name"];
					break;
				}
			}
		}

		function getAddValue()
		{
			return $this->value;
		}

		function getEditValue()
		{
			return $this->value;
		}

		function getEditHTML()
		{
			if (count($this->options) == 0)
				$this->loadFromDatabase();

			$template = \SmartyTools::getSmarty();
			$template->assign("name", $this->field);
			$template->assign("value", $this->getEditValue());
			$template->assign("options", $this->options);
			if (strlen(trim($this->help)) > 0)
				$template->assign("help", $this->help);
			else
				$template->assign("help", false);

			return $template->fetch("elements/".$this->templateFile);
		}

		function loadFromDatabase()
		{
			// Fetch options
			$query = "SELECT ".$this->keyfield.", ".$this->namefield." FROM ".$this->table;

			if ($this->deletedfield)
				$this->whereQuery .= ((strlen(trim($this->whereQuery))==0)?" WHERE ":" AND ").$this->deletedfield." = 0";

			$query .= " ".$this->whereQuery;

			if ($this->orderbyfield)
				$query .= " ORDER BY ".$this->orderbyfield;

			$db = \MySQL::getDB();
			if ($records = $db->getRows($query)) {
				foreach ($records as $record) {
					$this->addOption($record[0], $record[1]);
				}
			}
		}

		function addOption($value, $name)
		{
			$this->options[] = array("value" => $value, "name" => $name);
		}
	}
}

namespace AutoCompleteElement
{
	class Element extends \TextElement\Element
	{
		public $templateFile = "autocomplete";
		public $valueName = "";
		public $keyfield = null;
		public $namefield = null;
		public $table = null;
		public $orderbyfield = false;
		public $deletedfield = false;
		public $type = "relation";

		function getValueName()
		{
			if ($this->keyfield != null && $this->getNamefield() != null && $this->table != null)
			{
				if ($result = \MySQL::getDB()->getRow("SELECT ".$this->getNamefield()." FROM ".$this->table." WHERE ".$this->keyfield." = ?", array($this->value)))
					$this->valueName = $result[0];
			}
			return $this->valueName;
		}

		function getValue()
		{
			return $this->getValueName();
		}

		function getAddValue()
		{
			return $this->value;
		}

		function getNamefield()
		{
			if ($this->namefield == null)
				$this->namefield = self::calcNamefield($this->table);

			return $this->namefield;
		}

		function getEditHTML($extraAttributes=array())
		{
			\AppRoot::debug("GetEditHTML(".$this->field.") ".$this->templateFile." : " . $this->value);
			$template = \SmartyTools::getSmarty();
			$template->assign("element", $this);
			$template->assign("extraAttributes", $extraAttributes);
			return $template->fetch("elements/".$this->templateFile.".html");
		}

		/*** STATIC ***/
		public static function calcNamefield($table)
		{
			$namefield = "";
			$nameFields = array();

			foreach (explode(",",\Tools::REQUEST("namefield")) as $field)
			{
				if (strlen(trim($field)) > 0)
					$nameFields[] = \Tools::Escape(trim($field));
			}

			if (count($nameFields) > 0)
			{
				if (count($nameFields) > 1)
				{
					foreach ($nameFields as $field)
					{
						if (strlen(trim($namefield)) > 0)
							$namefield .= ",' - ',";
						$namefield .= $field;
					}
					$namefield = "CONCAT(".$namefield.")";
				}
				else
					$namefield = $nameFields[0];
			}

			return $namefield;
		}

		public static function getValues()
		{
			if (\Tools::REQUEST("element"))
			{
				$element = "";
				$parts = explode("-", \Tools::REQUEST("element"));
				foreach ($parts as $part) {
					if (strlen(trim($part)) > 0)
						$element .= '\\'.$part;
				}

				if (class_exists($element))
					return $element::getValues();
			}

			$results = array();
			$terms = array();

			$table = \Tools::Escape(\Tools::REQUEST("table"));
			$field = \Tools::Escape(\Tools::REQUEST("field"));
			$keyfield = \Tools::Escape(\Tools::REQUEST("keyfield"));
			$search = \Tools::Escape(\Tools::REQUEST("term"));
			$limit = \Tools::Escape(\Tools::REQUEST("limit"));
			$deletedField = \Tools::Escape(\Tools::REQUEST("deletedfield"));
			$searchMinLength = \Tools::Escape(\Tools::REQUEST("minsearchlen"))-0;

			if ($table == "article-gsm")
				$table = "articles";

			$terms = array();
			foreach (explode(" ",$search) as $term) {
				if (strlen(trim($term)) >= $searchMinLength)
					$terms[] = $term;
			}

			$query = "SELECT ".$keyfield.",".self::calcNamefield($table)." FROM ".$table." ";

			$queryWhere = "";
			if (\Tools::REQUEST("deletedfield"))
				$queryWhere .= " WHERE ".$deletedField." = 0 ";

			foreach ($terms as $term)
			{
				$queryWhere .= (strlen(trim($queryWhere))==0)?" WHERE ":" AND ".$namefield." = LIKE '%".$term."%' ";
			}

			$query .= $queryWhere;
			if ($limit)
				$query .= " LIMIT ".$limit;

			$records = \MySQL::getDB()->getRows($query);
			foreach ($records as $record)
			{
				$id = $record[0];
				$name = $record[1];
				$results[] = array("id"	=> $id, "label" => $name);
			}

			$return = "[\n";
			foreach ($results as $key => $result)
			{
				if ($key > 0)
					$return .= ",\n";

				$return .= "\t{";
				$j=0;
				foreach ($result as $field => $value)
				{
					$value = str_replace("'","",$value);
					$value = str_replace('"',"",$value);

					if ($j>0)
						$return .= ",";
					$return .= " \"".$field."\": \"".$value."\"";
					$j++;
				}
				$return .= " }";
			}
			$return .= "]";

			return $return;
		}
	}
}

namespace DateElement
{
	class Element extends \TextElement\Element
	{
		public $fullView = false;
		public $templateFile = "date";
		public $todayIfNull = true;

		function setValue($value)
		{
			if ($this->obligatory && strlen(trim($value)) == 0)
				return false;

			$this->value = date("Y-m-d", strtotime($value));
			return true;
		}

		function getValue()
		{
			if (strlen(trim($this->value)) == 0)
			{
				if ($todayIfNull)
					$this->setValue("now");
				else
					return "";
			}

			$viewDate = $this->value;

			if ($this->fullView)
			{
				$viewDate = round(date("d", strtotime($this->value)));
				$viewDate .= " " . \Tools::getFullMonth(date("m", strtotime($this->value))) . " ";
				$viewDate .= date("Y", strtotime($this->value));
			}
			else
				$viewDate = date("d-m-Y", strtotime($this->value));

			$value = $viewDate;
			$value = "<div style='white-space: nowrap;'>".$value."</div>";
			return $value;
		}

		function getAddValue()
		{
			if (strlen(trim($this->value)) == 0)
				$this->setValue("now");

			return date("Y-m-d", strtotime($this->value));
		}

		function getEditValue()
		{
			if (strlen(trim($this->value)) == 0)
				$this->setValue("now");

			return date("d-m-Y", strtotime($this->value));
		}
	}
}

namespace DateTimeElement
{
	class Element extends \TextElement\Element
	{
		public $fullView = false;

		function setValue($value)
		{
			if ($this->obligatory && strlen(trim($value)) == 0)
				return false;

			$this->value = date("Y-m-d H:i:s", strtotime($value));
			return true;
		}

		function getValue()
		{
			if (strlen(trim($this->value)) == 0)
				$this->setValue("now");

			$viewDate = $this->value;

			if ($this->fullView) {
				$viewDate = round(date("d", strtotime($this->value)));
				$viewDate .= " " . \Tools::getFullMonth(date("m", strtotime($this->value))) . " ";
				$viewDate .= date("Y", strtotime($this->value));
				$viewDate .= " ";
				$viewDate .= date("H:i", strtotime($this->value));
			} else
				$viewDate = date("d-m-Y H:i", strtotime($this->value));

			return $viewDate;
		}

		function getAddValue()
		{
			if (strlen(trim($this->value)) == 0)
				$this->setValue("now");

			return date("Y-m-d H:i:s", strtotime($this->value));
		}

		function getEditValue()
		{
			if (strlen(trim($this->value)) == 0)
				$this->setValue("now");

			return date("d-m-Y H:i:s", strtotime($this->value));
		}
	}
}

namespace CorporationElement
{
	class Element extends \TextElement\Element
	{
		function setValue($value)
		{
			if ($this->obligatory && strlen(trim($value)) == 0)
				return false;

			if (strlen(trim($value)) == 0) {
				$this->value = 0;
				return true;
			} else {
				$corporation = \eve\model\Corporation::findOne(["name" => $value]);
				if ($corporation) {
                    return true;
                } else {
					$this->errors[] = "Corporation `".$value."` not found.";
					$this->value = 0;
					return false;
				}
			}
		}

		function getValue()
		{
			$corporation = new \eve\model\Corporation($this->value);
			return $corporation->name;
		}

		function getAddValue()
		{
			return $this->value;
		}

		function getEditValue()
		{
			$corporation = new \eve\model\Corporation($this->value);
			return $corporation->name;
		}
	}
}

namespace AllianceElement
{
	class Element extends \TextElement\Element
	{
		function setValue($value)
		{
			if ($this->obligatory && strlen(trim($value)) == 0)
				return false;

			if (strlen(trim($value)) == 0)
			{
				$this->value = 0;
				return true;
			}
			else
			{
				$controller = new \eve\controller\Alliance();
				$alliance = $controller->getAllianceByName($value);

				if ($alliance->id > 0)
					return true;
				else
				{
					$this->errors[] = "Alliance `".$value."` not found.";
					$this->value = 0;
					return false;
				}
			}
		}

		function getValue()
		{
			$alliance = new \eve\model\Alliance($this->value);
			return $alliance->name;
		}

		function getAddValue()
		{
			return $this->value;
		}

		function getEditValue()
		{
			$alliance = new \eve\model\Alliance($this->value);
			return $alliance->name;
		}
	}
}
namespace PasswordElement
{
	class Element extends \TextElement\Element
	{
		public $templateFile = "password";

		function setValue($value)
		{
			if ($this->obligatory && strlen(trim($value)) == 0)
				return false;

			$this->value = \User::generatePassword($value);
			return true;
		}

		function getEditValue()
		{
			return "";
		}
	}
}
?>