<?php
class MySQL
{
	protected $host = MYSQL_HOST;
	protected $user = MYSQL_USER;
	protected $pass = MYSQL_PASS;
	protected $dtbs = MYSQL_DTBS;
	protected $connection = null;
	public $error = array();

	private static $currentDB = null;

    /**
     * get new mysql connection
     * @param array|bool $credentials
     * @param bool $newConnection
     */
	function __construct($credentials=false, $newConnection=false)
	{
		if ($credentials) {
			// Gebruik opgegeven connectie
			$this->host = $credentials["host"];
			$this->user = $credentials["user"];
			$this->pass = $credentials["pass"];
			$this->dtbs = $credentials["dtbs"];
		}
		$this->connect($newConnection);
	}

	/**
	 * Connect
	 * @param boolean $newConnection
	 */
	function connect($newConnection=false)
	{
		$this->connection = new \mysqli($this->host, $this->user, $this->pass, $this->dtbs);

		if ($this->connection->connect_errno == 0)
			\AppRoot::debug("MySQL: <span style='color: green;'>Connection established</span> (".$this->host." > ".$this->dtbs." [".$this->user."])");
		else
		{
			$this->error("Connection failed: ".$this->connection->connect_error." (".$this->host." > ".$this->dtbs." [".$this->user."])");
			$this->close();
		}
	}

	/**
	 * Get connection
	 * @return \mysqli|null
	 */
	function getConnection()
	{
		if (!$this->connected())
			$this->connect();

		return $this->connection;
	}

	/**
	 * Do we have a connection?
	 * @return boolean
	 */
	function connected()
	{
		if ($this->connection == null)
			return false;
		else
			return true;
	}

	/**
	 * Close connection
	 */
	function close()
	{
		if ($this->connected())
			$this->getConnection()->close($this->connection);

		$this->connection = null;
	}

	function error($msg, $error=null)
	{
		$this->error[] = $error;
		try {
			AppRoot::error("MySQL: <span style='color: red;'>" . $msg . "</span><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $error);
		}
		catch (Exception $e) {}
	}

	function replaceParams($query, $data = array())
	{
		$regex = "/(\?)(?=(?:[^']|'[^']*')*$)/";
		foreach ($data as $var) {
			$var = "'" . self::Escape($var) . "'";
			$query = preg_replace($regex, $var, $query, 1);
		}
		return $query;
	}


	/**
	 * Do query
	 * @param string $query
	 * @param array $data
	 * @return \mysqli_result|false
	 */
	function doQuery($query, $data=array())
	{
		if (!$this->connected())
			return false;

		if (count($data) > 0)
			$query = $this->replaceParams($query, $data);

		\AppRoot::debug("MySQL: Query (" . $this->host . " > " . $this->dtbs . ")\n<span style='color:#0000AA'>".$query."</span>");
		if ($result = $this->getConnection()->query($query))
			return $result;
		else
		{
			$this->error($this->getConnection()->error, $query);
			return false;
		}
	}

	function getRows($query, $data=array(), $lowercaseColumnNames=true)
	{
		if ($result = $this->doQuery($query, $data))
		{
			$results = array();
			while ($row = $result->fetch_array())
			{
				if ($lowercaseColumnNames) {
					foreach ($row as $key => $val) {
						$row[strtolower($key)] = $val;
					}
				}
				$results[] = $row;
			}
			$result->free_result();
			return $results;
		}

		return false;
	}

	function getRow($query, $data=array(), $lowercaseColumnNames=true)
	{
		if ($rows = $this->getRows($query, $data, $lowercaseColumnNames))
			return $rows[0];
		else
			return false;
	}

    /**
     * update or insert
     * @param      $table
     * @param      $data
     * @param array|bool $where
     * @return bool|int|string
     */
	function updateinsert($table, $data, $where=false)
	{
		if ($where)
		{
			$query = array();
			$i = 0;
			foreach ($where as $field => $value)
			{
				if ($value === null)
					$query[] = $field." is null";
				else
					$query[] = $field." = '".self::Escape($value)."'";

				$i ++;
			}
			$results = $this->getRows("SELECT * FROM ".$table." WHERE ".implode(" AND ", $query));
			if (count($results) > 0) {
				// Bestaat, update
				return $this->update($table, $data, $where);
			}
			else {
				// Bestaat niet, insert
				return $this->insert($table, $data);
			}
		}
		else
			$this->error("updateinsert expects where clause");

        return false;
	}

	function insert($table, $data, $onDuplicateUpdateKey=null)
	{
		$fields = array();
		$values = array();
		foreach ($data as $field => $value) {
			$fields[] = $field;
			$values[] = ($value === null)?"null":"'".self::Escape($value)."'";
		}

		$query = "INSERT INTO ".$table." (".implode(", ",$fields).") VALUES (".implode(", ",$values).")";

		if ($onDuplicateUpdateKey !== null)
		{
			$updates = array();
			foreach ($fields as $key => $field) {
				if ($field != $onDuplicateUpdateKey)
					$updates[] = $field." = ".$values[$key];
			}
			$query .= " ON DUPLICATE KEY UPDATE ".implode(", ",$updates);
		}

		if ($this->doQuery($query))
			return mysqli_insert_id($this->getConnection());
		else
			return false;
	}

    /**
     * update
     * @param string $table
     * @param array $data
     * @param array|bool $where
     * @return bool
     */
	function update($table, $data, $where=false)
	{
		$fields = array();
		$query = array();

		foreach ($data as $field => $value)
		{
			if ($value === null)
				$fields[] = $field." = null";
			else
				$fields[] = $field." = '".self::Escape($value)."'";
		}
		if ($where)
		{
			foreach ($where as $field => $value)
			{
				if ($value === null)
					$query[] = $field." is null";
				else
					$query[] = $field." = '".self::Escape($value)."'";
			}
		}

		if ($this->doQuery("UPDATE ".$table." SET ".implode(", ",$fields)." ".((count($query) > 0)?" WHERE ".implode(" AND ", $query):"")))
			return true;
		else
			return false;
	}

    /**
     * delete
     * @param string $table
     * @param array|bool $where
     * @return bool
     */
	function delete($table, $where=false)
	{
		if (! $where)
			$query = "TRUNCATE " . $table;
		else {
			$query = "DELETE FROM " . $table . " ";
			$i = 0;
			foreach ($where as $field => $value) {
				if ($i == 0)
					$query .= " WHERE ";
				else
					$query .= " AND ";
				$query .= $field . " = '" . self::Escape($value) . "'";
				$i ++;
			}
		}
		if ($this->doQuery($query))
			return true;
		else
			return false;
	}

	/**
	 * Convert tablenames / column names to lowercase
	 */
	function convertToLowerCase()
	{
        $queries = array();
		if ($tables = $this->getRows("	SELECT * FROM information_schema.tables
										WHERE table_schema = '".$this->dtbs."'"))
		{
			foreach ($tables as $table)
			{
				$tableName = $table["TABLE_NAME"];
				$queries[] = "RENAME TABLE ".$tableName." TO ".strtolower($tableName);
				$tableName = strtolower($table["TABLE_NAME"]);

				if ($columns = $this->getRows("	SELECT 	*
												FROM 	information_schema.columns
												WHERE 	table_schema = '".$this->dtbs."'
												AND		table_name = '".$tableName."'"))
				{
					foreach ($columns as $column)
					{
						$columnName = $column["COLUMN_NAME"];
						if (strtolower($column["COLUMN_KEY"]) == "pri" && strtolower($columnName) == "id")
							continue;

						$query = "ALTER TABLE ".$tableName." ";
						$query .= "CHANGE ".$columnName." ".strtolower($columnName)." ";
						$query .= $column["DATA_TYPE"].(($column["CHARACTER_MAXIMUM_LENGTH"] != null)?"(".$column["CHARACTER_MAXIMUM_LENGTH"].")":"")." ";
						$queries[] = $query;
					}
				}
			}
		}

		foreach ($queries as $query)
		{
			$this->doQuery($query);
		}
	}

	function makeBackUp($structureOnly=false, $backupFile=false)
	{
		\AppRoot::debug("MySQL: Start backup");

		$directoryParts = array();
		foreach (explode("/",$backupFile) as $part) {
			$directoryParts[] = $part;
		}
		$filename = "documents/".array_pop($directoryParts);
		$directory = implode("/", $directoryParts);

		\AppRoot::doCliCommand("mysqldump -h ".$this->host." -u ".$this->user." -p".$this->pass." --lock-tables=false ".$this->dtbs." > ".$filename);
		\AppRoot::doCliCommand("tar -czf ".$filename.".tar.gz ".$filename);
		\AppRoot::doCliCommand("mv ".$filename.".tar.gz ".$directory."/".str_replace("documents/","",$filename).".tar.gz");
		\AppRoot::doCliCommand("rm ".$filename);

		\AppRoot::debug("MySQL: Finished backup");
		return true;
	}

	public static function escape($value, $isDBField=false)
	{
		$db = self::getDB();
		if (!$db->connected())
		{
			$db = new MySQL();
			$_SESSION["mysql"] = $db;
		}

		$value = trim($value);
		$value = stripslashes($value);
		$value = mysqli_real_escape_string(self::getDB()->getConnection(), $value);

		if ($isDBField)
			$value = str_replace(";","",$value);

		return $value;
	}

	/**
	 * Get db instantie
	 * @return \MySQL
	 */
	public static function getDB()
	{
		if (self::$currentDB == null)
			self::$currentDB = new MySQL();

		return self::$currentDB;
	}
}
?>