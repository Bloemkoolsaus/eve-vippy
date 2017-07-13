<?php
class MySQL
{
    protected $host = null;
    protected $user = null;
    protected $pass = null;
    protected $dtbs = null;
    protected $connection = null;
    protected $charset = "utf8";
    public $error = array();

    /** @var MySQL */
    public static $db = null;
    protected static $connections = null;

    function __construct($credentials=false)
    {
        if ($credentials)
        {
            // Gebruik opgegeven connectie
            $this->host = $credentials["host"];
            $this->user = $credentials["user"];
            $this->pass = $credentials["pass"];
            $this->dtbs = $credentials["dtbs"];
        }
        else if (defined("MYSQL_HOST"))
        {
            $this->host = MYSQL_HOST;
            $this->user = MYSQL_USER;
            $this->pass = MYSQL_PASS;
            $this->dtbs = MYSQL_DTBS;
        }

        if ($this->host !== null)
            $this->connect();
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

    function connect()
    {
        if ($this->host == null)
            return false;

        $this->connection = new \mysqli($this->host, $this->user, $this->pass, $this->dtbs);
        if ($this->connection->connect_errno == 0)
        {
            \AppRoot::debug("MySQL: <span style='color: green;'>Connection established</span> (".$this->host." > ".$this->dtbs." [".$this->user."])");
            if (!$this->connection->set_charset($this->charset))
                $this->error("Could not set character ".$this->charset);

            return true;
        }
        else
        {
            $this->error("Connection failed: ".$this->connection->connect_error." (".$this->host." > ".$this->dtbs." [".$this->user."])");
            $this->close();
        }
        return false;
    }

    function close()
    {
        if ($this->connection != null)
            $this->getConnection()->close();
        $this->connection = null;
    }

    function connected()
    {
        if ($this->connection == null)
            return false;
        else
            return true;
    }

    function error($message, $error="", $store=true)
    {
        $this->error[] = array("descr" => $message, "error" => $error);
        try {
            $errorData = "Server:".$this->host."\n";
            $errorData .= "Schema:".$this->dtbs."\n";
            $errorData .= "\n\n".\AppRoot::getStackTrace()."\n\n";
            \AppRoot::error("MySQL (".$this->host."): ".$message."\n".$error."\n".$errorData,$store);

            if (!\AppRoot::doDebug())
            {
                // Parse error message
                $urgent = false;
                if (strpos(strtolower($message), "crashed") !== false)
                    $urgent = true;
                else if (strpos(strtolower($message), "doesn't exist") !== false)
                    $urgent = true;
                else if (strpos(strtolower($message), "unknown table") !== false)
                    $urgent = true;
                else if (strpos(strtolower($message), "out of memory") !== false)
                    $urgent = true;
                else if (strpos(strtolower($message), "too many connections ") !== false)
                    $urgent = true;
            }
        }
        catch (\Exception $e) {}
    }

    function replaceParams($query, $data = array())
    {
        $regex = "/(\\?)(?=(?:[^']|'[^']*')*$)/";
        foreach ($data as $var) {
            $var = "'" . self::escape($var) . "'";
            $query = preg_replace($regex, $var, $query, 1);
        }
        return $query;
    }

    /**
     * Do query
     * @param       $query
     * @param array $data
     * @return bool|\mysqli_result
     */
    function doQuery($query, $data=array())
    {
        if (count($data) > 0)
            $query = $this->replaceParams($query, $data);

        if ($this->connection == null)
            $this->connect();

        if (!$this->connected())
            return false;

        $execTime = microtime(true);

        if ($result = $this->getConnection()->query($query))
        {
            $execTime = microtime(true)-$execTime;
            \AppRoot::debug("MySQL: Query ($this->user@$this->host.$this->dtbs) ".
                (($result != null && isset($result->num_rows)) ? "[results: ".$result->num_rows."] " : "").
                (($execTime>0.05)?"<span style='color:red;'>":"")."[execution-time: ".number_format($execTime,4)."]".(($execTime>0.05)?"</span>":"").
                "\n[string]".$query."[/string]");

            if ($execTime > 0.05)
                \AppRoot::debug(\AppRoot::getStackTrace());

            return $result;
        }
        else
            $this->error($this->getConnection()->error, $query);

        return false;
    }

    /**
     * Get query results
     * @param string $query
     * @param array $data
     * @return array|boolean false
     */
    function getRows($query, $data=[], $lowercaseColumnNames=true)
    {
        if ($result = $this->doQuery($query, $data))
        {
            $results = array();
            while ($row = $result->fetch_array()) {
                if ($lowercaseColumnNames) {
                    foreach ($row as $key => $val) {
                        unset($row[$key]);
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

    /**
     * Get query result
     * @param string $query
     * @param array $data
     * @return array|boolean false
     */
    function getRow($query, $data=array())
    {
        if ($rows = $this->getRows($query, $data))
            return $rows[0];
        else
            return false;
    }

    /**
     * Create select query
     * @param string $columns
     * @return Query
     */
    function select($columns = "*")
    {
        $query = new Query;
        $query->setDB($this);
        $query->select($columns);

        return $query;
    }

    function updateinsert($table, $data, $where=array())
    {
        if ($where)
        {
            $params = array();
            $doInsert = false;

            foreach ($where as $field => $value) {
                if (count($where) == 1 && $field == "id") {
                    // De enige kolom is ID, en die is leeg. Inserten dus.
                    if ($value === null || $value == 0)
                        $doInsert = true;
                }

                if (is_bool($value))
                    $value = ($value)?1:0;

                if ($value === null)
                    $params[] = $field." is null";
                else
                    $params[] = $field." = ".((is_numeric($value)) ? $value : "'".self::escape($value)."'");
            }

            if (!$doInsert) {
                // Check of dit record al bestaat. Zo ja, moeten we updaten.
                $results = $this->getRows("SELECT * FROM " . $table . " WHERE " . implode(" AND ", $params));
                if (count($results) == 0)
                    $doInsert = true;   // Niet gevonden, insert.
            }

            if ($doInsert)
                return $this->insert($table, $data);
            else
                return $this->update($table, $data, $where);
        }
        else
            $this->error("updateinsert expects where clause");

        return false;
    }

    function insert($table, $data)
    {
        $fields = array();
        $values = array();

        foreach ($data as $field => $value)
        {
            $fields[] = $field;

            if (is_bool($value))
                $value = ($value)?1:0;

            if ($value === null || strlen(trim($value)) == 0)
                $values[] = "null";
            else
                $values[] = ((is_int($value)) ? $value : "'".self::escape($value)."'");
        }

        if ($this->doQuery("INSERT INTO ".$table." (".implode(",",$fields).") VALUES (".implode(",",$values).")"))
        {
            $id = $this->getRow("SELECT LAST_INSERT_ID()");
            return $id[0];
        }
        else
            return false;
    }

    function update($table, $data, $where = array())
    {
        $updateParams = array();
        $whereParams = array();

        foreach ($data as $field => $value)
        {
            if (is_bool($value))
                $value = ($value)?1:0;
            else if ($value === null || strlen(trim($value)) == 0)
                $value = "null";
            else
                $value = "'" . self::escape($value) . "'";

            $updateParams[] = $field." = ".$value;
        }
        if ($where)
        {
            foreach ($where as $field => $value)
            {
                if (is_bool($value))
                    $value = ($value)?1:0;

                if ($value === null)
                    $whereParams[] = $field." is null";
                else
                    $whereParams[] = $field." = ".((is_int($value)) ? $value : "'".self::escape($value)."'");
            }
        }

        if ($this->doQuery("UPDATE ".$table." SET ".implode(", ",$updateParams)." WHERE ".implode(" AND ",$whereParams)))
            return true;
        else
            return false;
    }

    function delete($table, $where = array())
    {
        if (!$where)
            $query = "TRUNCATE " . $table;
        else
        {
            $params = array();
            foreach ($where as $field => $value)
            {
                if (is_bool($value))
                    $value = ($value)?1:0;

                if ($value === null)
                    $params[] = $field." is null";
                else
                    $params[] = $field." = '".self::escape($value)."'";
            }
            $query = "DELETE FROM ".$table." WHERE ".implode(" AND ",$params);
        }

        if ($this->doQuery($query))
            return true;
        else
            return false;
    }

    function makeBackUp($backupFile)
    {
        \AppRoot::debug("MySQL: Start backup");

        $dumpFile = "/tmp/".$this->dtbs.".sql";

        \AppRoot::doCliCommand("mysqldump -h ".$this->host." -u ".$this->user." -p".$this->pass." --lock-tables=false ".$this->dtbs." > ".$dumpFile);
        \AppRoot::doCliCommand("tar -czf ".$backupFile." -C ".$dumpFile);
        \AppRoot::doCliCommand("rm ".$dumpFile);

        \AppRoot::debug("MySQL: Finished backup");
    }

    function makeDump($dumpFile)
    {
        if (!$dumpFile)
            $dumpFile = "documents/".date("Ymd").".sql";

        \AppRoot::doCliCommand("mysqldump -h ".$this->host." -u ".$this->user." -p".$this->pass." --lock-tables=false ".$this->dtbs." > ".$dumpFile);

        return $dumpFile;
    }

    /**
     * Quote a value for use in an SQL query
     * @param $value
     * @return string
     */
    public function quote($value)
    {
        $value = mysqli_real_escape_string(self::getDB()->getConnection(), $value);
        return "'".$value."'";
    }

    public static function escape($value, $isDBField=false)
    {
        if (!is_string($value))
            $value = (string)$value;

        $value = trim($value);
        $value = stripslashes($value);

        if (self::getDB()->connected())
            $value = mysqli_real_escape_string(self::getDB()->getConnection(), $value);

        if ($isDBField)
            $value = str_replace(";","",$value);

        return $value;
    }

    /**
     * Get database connection
     * @return \MySQL
     */
    public static function getDB()
    {
        $connected = false;
        if (self::$db != null && is_object(self::$db))
            $connected = self::$db->connected();

        if (!$connected)
            self::$db = new \MySQL();

        return self::$db;
    }

    /**
     * Get database connection
     * @return \MySQL
     */
    public static function getSlaveDB()
    {
        if (!defined("MYSQL_SLAVE_HOST"))
            return self::getDB();

        if (!isset(self::$connections[MYSQL_SLAVE_HOST]))
        {
            self::$connections[MYSQL_SLAVE_HOST] = new \MySQL([
                "host"	=> MYSQL_SLAVE_HOST,
                "user"	=> MYSQL_SLAVE_USER,
                "pass"	=> MYSQL_SLAVE_PASS,
                "dtbs"	=> MYSQL_SLAVE_DTBS
            ]);
        }

        return self::$connections[MYSQL_SLAVE_HOST];
    }

    function getTables($schema=null)
    {
        if (!$schema)
            $schema = $this->dtbs;

        $tables = [];
        if ($results = $this->getRows("select * from information_schema.tables where table_schema = '".$schema."'")) {
            foreach ($results as $table) {
                if (isset($table["table_name"]))
                    $tables[] = $table["table_name"];
            }
        }
        return $tables;
    }

    function tableToLowercase()
    {
        \AppRoot::doCliOutput("Rename tables to lowercase");
        foreach ($this->getTables() as $table) {
            \AppRoot::doCliOutput("> ".$table);
            $this->doQuery("rename table `".$table."` to `".strtolower($table)."`");
        }
    }

    function convertToUTF8($charset="utf8")
    {
        \AppRoot::setMaxExecTime(0);
        \AppRoot::doCliOutput("MySQL: Start charset conversion to ".$charset);

        foreach ($this->getTables() as $table) {
            \AppRoot::doCliOutput("> ".$table);
            $this->doQuery("alter table `".$table."` convert to character set '".$charset."'");
        }
    }

    function convertEngine($engine="InnoDB")
    {
        \AppRoot::setMaxExecTime(0);
        \AppRoot::doCliOutput("MySQL: Start ".$engine." conversion");

        foreach ($this->getTables() as $table) {
            \AppRoot::doCliOutput("> ".$table);
            $this->doQuery("alter table `".$table."` engine=".$engine);
        }
    }
}