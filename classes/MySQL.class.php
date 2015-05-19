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
			$this->connection->close($this->connection);

		$this->connection = null;
	}

	function error($msg, $error)
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

	function delete($table, $where = false)
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
		$filename = array_pop($directoryParts);
		$directory = implode("/", $directoryParts);

		\AppRoot::doCliCommand("mysqldump -h ".$this->host." -u ".$this->user." -p".$this->pass." --lock-tables=false ".$this->dtbs." > ".$filename);
		\AppRoot::doCliCommand("tar -czf ".$filename.".tar.gz ".$filename);
		\AppRoot::doCliCommand("mv ".$filename.".tar.gz ".$directory."/".$filename.".tar.gz");
		\AppRoot::doCliCommand("rm ".$filename);

		\AppRoot::debug("MySQL: Finished backup");
		return true;
	}

	function emptyDatabase()
	{
		if ($tables = $this->getRows("SELECT * FROM information_schema.tables WHERE table_schema = '".MYSQL_DTBS."'")) {
			foreach ($tables as $table) {
				$this->doQuery("DROP TABLE IF EXISTS `".$table["TABLE_NAME"]."`");
			}
		}
	}

	function copyFromDatabase($host=false,$username=false,$password=false,$schema=false,$structureOnly=false)
	{
		$this->emptyDatabase();

		$credentials = array("host"	=> (!$host) ? MYSQL_HOST : $host,
							"user"	=> (!$username) ? MYSQL_USER : $username,
							"pass"	=> (!$password) ? MYSQL_PASS : $password,
							"dtbs"	=> (!$schema) ? MYSQL_DTBS : $schema);
		$copyDB = new MySQL($credentials);
		foreach (explode(";",$copyDB->makeBackUp($structureOnly)) as $query)
		{
			$this->doQuery($query);
		}
	}

	function syncStructureFromDatabase($host=false,$username=false,$password=false,$schema=false,$toScript=false)
	{
		// EERST BACKUP MAKEN
		$rootDir = "bak";
		if (!file_exists($rootDir))
			mkdir($rootDir,0777);

		$rootDir .= "/dbsync";
		if (!file_exists($rootDir))
			mkdir($rootDir,0777);

		$filename = $rootDir."/".date("YmdHi").".sql";
		$handle = fopen($filename,"w");
		fwrite($handle, $this->makeBackUp());
		fclose($handle);


		// START SYNCHRONIZATIE
		$credentials = array("host"	=> (!$host) ? MYSQL_HOST : $host,
							"user"	=> (!$username) ? MYSQL_USER : $username,
							"pass"	=> (!$password) ? MYSQL_PASS : $password,
							"dtbs"	=> (!$schema) ? MYSQL_DTBS : $schema);
		$srcDB = new MySQL($credentials);

		$localTables = array();
		$sourceTables = array();
		$syncQueries = array();

		if ($results = $this->getRows("SELECT * FROM information_schema.columns WHERE table_schema = ?", array($this->dtbs)))
		{
			foreach ($results as $result)
			{
				$localTables[$result["TABLE_NAME"]]["columns"][$result["COLUMN_NAME"]] = array(
						"type"		=> $result["COLUMN_TYPE"],
						"extra"		=> $result["EXTRA"],
						"null"		=> $result["IS_NULLABLE"],
						"default"	=> $result["COLUMN_DEFAULT"]
				);

				// Keys
				if ($results = $this->getRows("SHOW KEYS FROM ".$this->dtbs.".".$result["TABLE_NAME"]))
				{
					foreach ($results as $result) {
						$localTables[$result["Table"]]["keys"][$result["Key_name"]][$result["Column_name"]] = $result["Column_name"];
					}
				}
			}

		}

		if ($results = $srcDB->getRows("SELECT * FROM information_schema.columns WHERE table_schema = ?", array($srcDB->dtbs)))
		{
			foreach ($results as $result)
			{
				$sourceTables[$result["TABLE_NAME"]]["columns"][$result["COLUMN_NAME"]] = array(
						"type"		=> $result["COLUMN_TYPE"],
						"extra"		=> $result["EXTRA"],
						"null"		=> $result["IS_NULLABLE"],
						"default"	=> $result["COLUMN_DEFAULT"]
				);

				// Keys
				if ($results = $srcDB->getRows("SHOW KEYS FROM ".$srcDB->dtbs.".".$result["TABLE_NAME"]))
				{
					foreach ($results as $result) {
						$sourceTables[$result["Table"]]["keys"][$result["Key_name"]][$result["Column_name"]] = $result["Column_name"];
					}
				}
			}
		}

		// Controleer alle tabellen
		foreach ($sourceTables as $table => $tinfo)
		{
			if (isset($localTables[$table]))
			{
				// Tabel bestaat in lokale tabel

				// Controleer alle kolommen
				$prevColumn = "";
				foreach ($tinfo["columns"] as $column => $cinfo)
				{
					if (isset($localTables[$table]["columns"][$column]))
					{
						// Kolom bestaat in lokale tabel, controleer datatype
						$diff = false;
						foreach ($cinfo as $ivar => $ival)
						{
							if ($localTables[$table]["columns"][$column][$ivar] != $ival)
							{
								$diff = true;
								break;
							}
						}

						if ($diff)
						{
							// Vershil. Bijwerken
							$query = "ALTER TABLE `".$table."` MODIFY `".$column."` ".$cinfo["type"];
							if ($cinfo["null"] == "NO")
								$query .= " NOT NULL";
							if (strlen(trim($cinfo["default"])) > 0)
								$query .= " DEFAULT '".$cinfo["default"]."'";
							if (strlen(trim($cinfo["extra"])) > 0)
								$query .= " ".$cinfo["extra"];

							$syncQueries[] = $query;
						}
					}
					else
					{
						// Kolom bestaat niet in lokale tabel. Maak de kolom aan.
						$query = "ALTER TABLE `".$table."` ADD COLUMN `".$column."` ".$cinfo["type"];
						if ($cinfo["null"] == "NO")
							$query .= " NOT NULL";
						if (strlen(trim($cinfo["default"])) > 0)
							$query .= " DEFAULT '".$cinfo["default"]."'";
						if (strlen(trim($cinfo["extra"])) > 0)
							$query .= " ".$cinfo["extra"];
						if (strlen(trim($prevColumn)) > 0)
							$query .= " AFTER `".$prevColumn."`";

						$syncQueries[] = $query;
					}

					$prevColumn = $column;
				}

				// Controleer verwijderde kolommen
				foreach ($localTables[$table]["columns"] as $column => $cinfo)
				{
					if (!isset($sourceTables[$table]["columns"][$column]))
						$this->doQuery("ALTER TABLE `".$table."` DROP COLUMN `".$column."`");
				}

				// Controleer alle keys
				foreach ($tinfo["keys"] as $keyname => $columns)
				{
					$keyDiff = false;
					if (isset($localTables[$table]["keys"][$keyname]))
					{
						// Key bestaat.
						// Controleer of alle keys in de lokale tabel zitten
						foreach ($sourceTables[$table]["keys"][$keyname] as $column)
						{
							if (!isset($localTables[$table]["keys"][$keyname][$column]))
								$keyDiff = true;
						}
						// Controleer of er niet teveel keys in de lokalte tabel zitten
						foreach ($localTables[$table]["keys"][$keyname] as $column)
						{
							if (!isset($sourceTables[$table]["keys"][$keyname][$column]))
								$keyDiff = true;
						}

						if ($keyDiff)
						{
							// Key verwijderen
							$syncQueries[] = "DROP INDEX `".$keyname."` ON `".$table."`";
						}
					}
					else
						$keyDiff = true;

					if ($keyDiff)
					{
						// Key toevoegen
						$addQuery = "CREATE INDEX `".$keyname."` ON `".$table."` (";
						$i=0;
						foreach ($sourceTables[$table]["keys"][$keyname] as $column) {
							$addQuery .= (($i>0)?",":"")."`".$column."`";
						}
						$addQuery .= ")";
						$syncQueries[] = $addQuery;
					}
				}

				// Zijn er keys die verwijderd moeten worden?
				foreach ($localTables[$table]["keys"] as $keyname => $columns)
				{
					if (!isset($sourceTables[$table]["keys"][$keyname]))
						$syncQueries[] = "DROP INDEX `".$keyname."` ON `".$table."`";
				}
			}
			else
			{
				// Tabel bestaat niet in lokale tabel. Maak de tabel aanm.
				if ($createtable = $srcDB->getRow("SHOW CREATE TABLE ".$srcDB->dtbs.".".$table))
					$syncQueries[] = $createtable[1];
			}
		}

		// Controleer verwijderde tabellen
		foreach ($localTables as $table => $tinfo)
		{
			if (!isset($sourceTables[$table]))
				$syncQueries[] = "DROP TABLE `".$table."`";
		}

		if ($toScript)
		{
			$rootDir = "sync";
			if (!file_exists($rootDir))
				mkdir($rootDir,0777);

			$filename = $rootDir."/".date("YmdHi").".sql";
			$handle = fopen($filename,"w");
			foreach ($syncQueries as $query) {
				fwrite($handle,$query.";\n\n");
			}
			fclose($handle);
		}
		else
		{
			foreach ($syncQueries as $query) {
				$this->doQuery($query);
			}
		}
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