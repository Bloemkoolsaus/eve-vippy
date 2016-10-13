<?php
class Section
{
	public $table;
	public $keyfield;
	public $keyfielddb;
	public $keyvalue = false;
	public $deletedfield = null;
	public $groupbyfield = null;
	public $orderbyfield = null;
	public $updatefield = null;
	public $allowEdit = false;
	public $allowNew = null;
	public $allowDelete = null;
	public $allowSortOrder = false;
	public $allowSearch = true;
	public $allowSearchSort = true;
	public $subSection = false;

	public $fromQuery;
	public $whereQuery;
	public $whereQueryParams = array();
	public $orderBy;
	public $orderByDir = "ASC";
	public $searchQuery;
	public $staticfields = array();

	public $elements = array();
	private $records = array();
	public $limit = 30;

	private $db;
	public $urlOverview;
	public $urlNew;
	public $urlEdit;
	public $urlDelete;
	public $urlSort;
	public $urlKeyField = "id";
	public $deleteMessage = "";

	function __construct($table, $keyfield=false)
	{
		$this->db = MySQL::getDB();
		$this->table = $table;

		if (is_array($keyfield)) {
			$this->keyfield = $keyfield[0];
			$this->keyfielddb = $keyfield[1];
		} else {
			$this->keyfield = $keyfield;
			$this->keyfielddb = $keyfield;
		}

		$this->keyvalue = Tools::REQUEST($this->keyfield);
	}

	public function getOverview()
	{
		if ($this->allowNew === null)
			$this->allowNew = $this->allowEdit;

		if ($this->allowDelete === null)
			$this->allowDelete = $this->allowEdit;

		// Check of we acties moeten uitvoeren!
		if (Tools::REQUEST("action") == "delete" && strlen(trim($this->urlDelete)) == 0) {
			if ($this->allowEdit)
				$this->delete();
		}

		if (Tools::REQUEST("action") == "edit" && $this->allowEdit)
			return $this->getEditForm();

		if (Tools::REQUEST("action") == "new" && $this->allowNew)
			return $this->getEditForm();

		if (Tools::REQUEST("action") == "sort" && strlen(trim($this->urlSort)) == 0) {
			if ($this->allowSortOrder) {
				if (Tools::REQUEST("direction") == "up")
					$this->moveUp();
				else
					$this->moveDown();
			}
		}

		return $this->getOverviewTable();
	}

	public function getOverviewTable()
	{
		AppRoot::debug("Section getOverviewTable(".$this->table.")");
		$searchTerm = Tools::REQUEST("search".$this->table);

		if ($this->subSection)
			$this->keyvalue = false;

		if (Tools::REQUEST("sort"))
			$this->orderBy = Tools::REQUEST("sort");

		if (Tools::REQUEST("dir"))
			$this->orderByDir = Tools::REQUEST("dir");

		$nrPages = 1;
		$pagenr = Tools::REQUEST("page");
		if ($pagenr < 1)
			$pagenr = 1;

		$table = \SmartyTools::getSmarty();
		$query = $this->buildQuery($this->keyvalue, $pagenr-1);
		$this->records = $this->db->getRows($query["query"], $query["params"]);
		$table->assign("nrRecords", count($this->records));

		AppRoot::debug("pagenr: ".$pagenr);
		if (count($this->records) == $this->limit || $pagenr > 1) {
			$query = $this->buildQuery($this->keyvalue, $pagenr-1, true);
			if ($count = $this->db->getRow($query["query"], $query["params"]))
				$nrPages = ceil($count[0]/$this->limit);
		}

		AppRoot::debug("nrpages: ".$nrPages);

		$head = array();
		foreach ($this->elements as $element)
		{
			if (!$element->inOverview)
				continue;

			$headTitle = array("title" => $element->title, "field" => $element->field);
			if (strlen(trim($element->help)) > 0)
				$headTitle["help"] = $element->help;
			else
				$headTitle["help"] = false;

			$head[] = $headTitle;
		}

		$rows = array();
		if (is_array($this->records) && count($this->records) > 0)
		{
			foreach ($this->records as $key => $result)
			{
				$row = array();
				$row["fields"] = array();
				foreach ($this->elements as $element)
				{
					if (!$element->inOverview)
						continue;

					$element->value = $result[$element->field];

					$field = array();
					$field["value"] = $element->getValue();

					if ($element->isNumber)
						$field["align"] = "right";
					else if ($element->isBoolean)
						$field["align"] = "center";
					else
						$field["align"] = "left";

					$row["fields"][] = $field;
				}
				$row["urlsort"] = $this->getSortURL($result[$this->keyfield]);
				$row["urledit"] = $this->getEditURL($result[$this->keyfield]);
				$row["urldelete"] = $this->getDeleteURL($result[$this->keyfield]);
				$rows[] = $row;
			}
		}

		$table->assign("heads", $head);
		$table->assign("rows", $rows);
		$table->assign("tablename", $this->table);
		$table->assign("search", $searchTerm);
		$table->assign("urlnew", $this->getNewURL());
		$table->assign("urloverview", $this->getOverviewURL());
		$table->assign("deleteConfirmation", $this->deleteMessage);

		if ($this->allowSortOrder && $this->orderbyfield == null) {
			$this->allowSortOrder = false;
			AppRoot::error("No orderby field specified. Setting allowSortOrder to false!");
		}

		$table->assign("allowedit", $this->allowEdit);
		$table->assign("allownew", $this->allowNew);
		$table->assign("allowdelete", $this->allowDelete);
		$table->assign("allowsorting", $this->allowSortOrder);
		$table->assign("allowsearch", $this->allowSearch);

		$table->assign("nrPages", $nrPages);
		$table->assign("pageNr", $pagenr);
		$table->assign("prevPage", $pagenr-1);
		$table->assign("nextPage", $pagenr+1);

		$table->assign("orderByField", $this->orderBy);
		$table->assign("orderByDir", $this->orderByDir);

		return $table->fetch("section/overview");
	}

	public function getEditForm()
	{
		$errors = array();
		if (Tools::POST("save"))
		{
			$updData = array();
			foreach ($this->elements as $key => $element)
			{
				if (!$element->inEdit)
					continue;

				if ($element->setValue(Tools::POST($element->field)))
					$updData[$element->field] = $element->getAddValue();
				else
				{
					$elementErrors = $element->getErrors();
					if (count($elementErrors)>0)
					{
						foreach ($elementErrors as $error) {
							$errors[] = $error;
						}
					}
					else
						$errors[] = "'".$element->title . "' is empty or has an invalid value.";
				}

				AppRoot::debug($element->field.": ".$element->getAddValue()." (".$element->getValue().")");
			}

			if (count($errors) == 0)
			{
				$this->save();
				AppRoot::redirect($this->getOverviewURL());
			}
		}

		$template = \SmartyTools::getSmarty();
		$template->assign("keyField", $this->keyfield);
		$template->assign("errors", $errors);

		if (!$this->keyvalue || $this->keyvalue == "new")
			$this->keyvalue = "new";
		else {
			$query = $this->buildQuery($this->keyvalue);
			if (!$record = $this->db->getRow($query["query"], $query["params"]))
				return "Item niet gevonden.";
		}
		$template->assign("keyValue", $this->keyvalue);

		$fields = array();
		foreach ($this->elements as $element)
		{
			if (isset($record))
				$element->value = $record[$element->field];

			if (!$element->inEdit)
				continue;

			$field = array();
			$field["title"] = $element->title;
			$field["obligatory"] = ($element->obligatory)?1:0;

			if ($this->allowEdit && $element->allowEdit)
				$field["input"] = $element->getEditHTML();
			else
				$field["input"] = $element->getValue();

			$fields[] = $field;
		}
		$template->assign("elements", $fields);
		$template->assign("allowedit", $this->allowEdit);
		$template->assign("formname", $this->table);
		return $template->fetch("section/form");
	}

	public function addStaticField($field, $value)
	{
		$this->staticfields[$field] = $value;
	}

	private function save()
	{
		$updData = array();
		foreach ($this->elements as $key => $element)
		{
			if ($element->inEdit && $element->allowEdit)
				$updData[$element->field] = $element->getAddValue();
		}

		foreach ($this->staticfields as $field => $value)
		{
			if (!isset($updData[$field]))
				$updData[$field] = $value;
		}

		if ($this->updatefield != null)
			$updData[$this->updatefield] = date("Y-m-d H:i:s");

		if ($this->keyvalue == "new") {
			// Sortering..? Voeg artikel toe als laatste
			if ($this->orderbyfield) {
				$newSorting = 0;
				$query = "SELECT MAX(".$this->orderbyfield.") FROM ".$this->table;
				if ($this->deletedfield)
					$query .= " WHERE ".$this->deletedfield." = 0";
				if ($sort = $this->db->getRow($query)) {
					if (strlen(trim($sort[0])) > 0)
						$newSorting = $sort[0];
				}
				$newSorting += 1;
				$updData[$this->orderbyfield] = $newSorting;
			}
			$this->keyvalue = $this->db->insert($this->table, $updData);
		} else
			$this->db->update($this->table, $updData, array($this->keyfield => $this->keyvalue));
	}

	public function addElement($title, $field, $dbfield=false, $type=false)
	{
		AppRoot::debug("AddElement(".$title.",".$field.",".$dbfield.",".$type.")");
		if (!$type)
			$type = "TextElement";

		if (!class_exists($type))
			$type .= "\Element";

		$element = new $type($title, $field, $dbfield);
		$this->elements[$field] = $element;
		return $element;
	}

	public function getElement($element)
	{
		return $this->elements[$element];
	}

	public function getActionURL($action, $keyvalue="")
	{
		$exceptions = false;
		if (!$this->subSection || $action == "new" || $action == "edit" || $action == "delete")
			$exceptions = true;

		$exceptParams = array("page", "sort", "dir", "direction");
		if ($exceptions) {
			$exceptParams[] = "action";
			$exceptParams[] = $this->keyfield;
		}

		$url = Tools::getCurrentURL($exceptParams);
		if ($exceptions)
			$url .= "&action=".$action;

		return $url;
	}

	public function getOverviewURL()
	{
		$url = "";
		if (strlen(trim($this->urlOverview)) > 0)
			$url = $this->urlOverview;
		else
			$url = $this->getActionURL("overview");

		return $url;
	}

	public function getNewURL()
	{
		$url = "";
		if (strlen(trim($this->urlNew)) > 0)
			$url = $this->urlNew;
		else
			$url = $this->getActionURL("new");

		return $url;
	}

	public function getSortURL($keyvalue)
	{
		$url = "";
		if (strlen(trim($this->urlSort)) > 0)
			$url = $this->urlSort;
		else
			$url = $this->getActionURL("sort", $keyvalue);

		$url .= "&".$this->urlKeyField."=".$keyvalue;
		return $url;
	}

	public function getEditURL($keyvalue)
	{
		$url = "";
		if (strlen(trim($this->urlEdit)) > 0)
			$url = $this->urlEdit;
		else
			$url = $this->getActionURL("edit", $keyvalue);

		$url .= "&".$this->urlKeyField."=".$keyvalue;
		return $url;
	}

	public function getDeleteURL($keyvalue)
	{
		$url = "";
		if (strlen(trim($this->urlEdit)) > 0)
			$url =  $this->urlDelete;
		else
			$url = $this->getActionURL("delete", $keyvalue);

		$url .= "&".$this->urlKeyField."=".$keyvalue;
		return $url;
	}

	public function delete()
	{
		AppRoot::debug($this->table." -> delete()");
		if ($this->deletedfield != null && Tools::REQUEST($this->keyfield))
		{
			$what = array($this->deletedfield => 1);
			if ($this->updatefield != null)
				$what[$this->updatefield] = date("Y-m-d H:i:s");
			$who = array($this->keyfield => Tools::REQUEST($this->keyfield));
			$this->db->update($this->table, $what, $who);

			$this->correctSortOrder();
		}
		AppRoot::redirect($this->getOverviewURL());
	}

	public function moveUp()
	{
		AppRoot::debug($this->table." -> moveUp()");
		$this->moveSorting("up");
		AppRoot::redirect($this->getOverviewURL());
	}

	public function moveDown()
	{
		AppRoot::debug($this->table." -> moveDown()");
		$this->moveSorting("down");
		AppRoot::redirect($this->getOverviewURL());
	}

	public function moveSorting($direction="up")
	{
		// Haal huidig record
		$qParams = array();
		$query = "SELECT ".$this->keyfield.", ".$this->orderbyfield."
					FROM ".$this->table."
					WHERE ".$this->keyfield." = ?";
		$qParams[] = Tools::REQUEST($this->keyfield);

		foreach ($this->staticfields as $field => $value) {
			$query .= " AND `".$field."` = ?";
			$qParams[] = $value;
		}

		if ($record = $this->db->getRow($query, $qParams))
		{
			$curid = $record[$this->keyfield];
			$cursort = $record[$this->orderbyfield];

			$qWhere = "";
			if ($this->deletedfield != null)
				$qWhere = " AND ".$this->deletedfield." = 0 ";

			$qParams = array();
			foreach ($this->staticfields as $field => $value) {
				$qWhere .= " AND `".$field."` = ?";
				$qParams[] = $value;
			}

			$query = "SELECT ".$this->keyfield.", ".$this->orderbyfield." ";
			$query .= "FROM ".$this->table."  ";
			if ($direction == "up") {
				$query .= "WHERE ".$this->orderbyfield." < ".$cursort." ".$qWhere." ";
				$query .= "ORDER BY ".$this->orderbyfield." DESC ";
			} else {
				$query .= "WHERE ".$this->orderbyfield." > ".$cursort." ".$qWhere." ";
				$query .= "ORDER BY ".$this->orderbyfield." ASC ";
			}
			$query .=" LIMIT 1";

			if ($record = $this->db->getRow($query, $qParams))
			{
				// We hebben iemand om mee te ruilen!
				$moveid = $record[$this->keyfield];
				$movesort = $record[$this->orderbyfield];

				// Nou..... Ruilen dan!!
				$what = array($this->orderbyfield => $movesort);
				if ($this->updatefield != null)
					$what[$this->updatefield] = date("Y-m-d H:i:s");
				$who = array($this->keyfield => $curid);
				$this->db->update($this->table, $what, $who);

				$what = array($this->orderbyfield => $cursort);
				if ($this->updatefield != null)
					$what[$this->updatefield] = date("Y-m-d H:i:s");
				$who = array($this->keyfield => $moveid);
				$this->db->update($this->table, $what, $who);

				$this->correctSortOrder();
			}
		}
	}

	public function correctSortOrder()
	{
		if (!$this->orderbyfield)
			return;

		$query = "SELECT ".$this->keyfield." FROM ".$this->table;

		$qWhere = "";
		if ($this->deletedfield != null)
			$qWhere = " WHERE ".$this->deletedfield." = 0 ";

		$qParams = array();
		foreach ($this->staticfields as $field => $value) {
			$qWhere .= ((strlen(trim($qWhere))>0)?" AND ":" WHERE ") . " `".$field."` = ?";
			$qParams[] = $value;
		}

		$query .= $qWhere;
		$query .= " ORDER BY ".$this->orderbyfield;

		if ($records = $this->db->getRows($query, $qParams))
		{
			foreach ($records as $key => $record)
			{
				$what = array($this->orderbyfield => $key);
				if ($this->updatefield != null)
					$what[$this->updatefield] = date("Y-m-d H:i:s");
				$who = array($this->keyfield => $record[$this->keyfield]);
				$this->db->update($this->table, $what, $who);
			}
		}
	}

	private function buildQuery($keyValue=false, $pagenr=0, $count=false)
	{
		$querySelect = "";
		$queryFrom = "";
		$queryWhere = "";
		$queryOrder = "";

		$limit = "";
		if ($count)
			$querySelect = "SELECT COUNT(".$this->keyfielddb.")";
		else
		{
			$limit = ($pagenr*$this->limit).", ".$this->limit;

			$querySelect = "SELECT ".$this->keyfielddb;
			foreach ($this->elements as $key => $element)
			{
				$querySelect .= ", ";
				if ($element->dbfield == $element->field)
					$querySelect .= $element->field;
				else
					$querySelect .= $element->dbfield." AS ".$element->field;
			}
		}

		// From
		$queryFrom = " FROM ";
		if (strlen(trim($this->fromQuery)) > 0)
			$queryFrom .= $this->fromQuery;
		else
			$queryFrom .= $this->table;

		// Where
		$qParams = array();
		if ($keyValue)
		{
			$queryWhere = " WHERE " . $this->keyfielddb . " = ?";
			$qParams[] = $keyValue;
		}
		else
		{
			if (strlen(trim($this->whereQuery)) > 0) {
				$queryWhere .= $this->whereQuery;
				foreach ($this->whereQueryParams as $val) {
					$qParams[] = $val;
				}
			}

			foreach ($this->staticfields as $field => $value) {
				$queryWhere .= (strlen(trim($queryWhere)) > 0) ? " AND " : " WHERE ";
				$queryWhere .= "`".$field."` = ?";
				$qParams[] = $value;
			}

			if (strlen(trim($this->deletedfield)) > 0) {
				$queryWhere .= (strlen(trim($queryWhere)) > 0) ? " AND " : " WHERE ";
				$queryWhere .= $this->deletedfield . " = 0";
			}

			// Search
			$searchTerm = \Tools::REQUEST("search".$this->table);
			$keywords = array();
			if ($searchTerm)
			{
				$keywords = str_replace(","," ", $searchTerm);
				$keywords = str_replace(";"," ", $keywords);
				$keywords = explode(" ", $keywords);
				$querySearch = array();
				$querySearchParams = array();
				foreach ($keywords as $keyword) {
					$keyword = \Tools::Escape($keyword);
					$querySearchWord = "";

					if (strlen(trim($this->searchQuery)) > 0)
						$querySearchWord = str_replace("?", "'%".$keyword."%'", $this->searchQuery);

					foreach ($this->elements as $key => $element) {
						if ($element->searchable) {
							if ($element->type == "relation") {
								$querySearchWord .= (strlen(trim($querySearchWord)) > 0) ? " OR " : "";
								$querySearchWord .= $element->dbfield . " IN (SELECT ".$element->keyfield." FROM ".$element->table." WHERE ".$element->namefield." LIKE '%".$keyword."%') ";
							} else {
								$querySearchWord .= (strlen(trim($querySearchWord)) > 0) ? " OR " : "";
								$querySearchWord .= $element->dbfield . " LIKE '%".$keyword."%' ";
							}
						}
					}

					$queryWhere .= (strlen(trim($queryWhere)) > 0) ? " AND " : " WHERE ";
					$queryWhere .= " (".$querySearchWord.") ";
				}

				if (count($keywords) > 0)
					$limit = ($this->limit*2);
			}

			// Group by
			if (!$count)
			{
				if (strlen(trim($this->groupbyfield)) == 0)
					$this->groupbyfield = $this->keyfielddb;
				$queryWhere .= " GROUP BY ".$this->groupbyfield;

				// Order by
				if (strlen(trim($this->orderBy)) == 0 && $this->orderbyfield)
					$this->orderBy = $this->orderbyfield;

				if (strlen(trim($this->orderBy)) > 0)
					$queryOrder = "ORDER BY ".$this->orderBy." ".$this->orderByDir;
			}
		}

		$query = $querySelect." ".$queryFrom." ".$queryWhere." ".$queryOrder;
		if (!$count)
			$query .= " LIMIT ". $limit;

		return array("query" => $query, "params" => $qParams);
	}
}
?>