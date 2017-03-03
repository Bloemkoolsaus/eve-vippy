<?php
class Config
{
	private $config = null;
	private static $configObject = null;

	/**
	 * Get config value
	 * @param string $var
	 * @return mixed|boolean
	 */
	function get($var)
	{
		if ($this->config == null)
			$this->fetch();

		if (!isset($this->config[$var])) {
			if ($result = \MySQL::getDB()->getRow("SELECT val FROM system_config WHERE var = ?", array($var)))
				$this->config[$var] = $result["val"];
		}

		if (isset($this->config[$var])) {
			$value = trim($this->config[$var]);
            if (strlen(trim($value)) > 0) {
                // Check of het misschien json is, zo ja, parsen!
                if ($value[0] == "{" && $value[strlen($value) - 1] == "}")
                    $value = json_decode($value);
            }
			return $value;
		}

		return false;
	}

	function set($var, $value)
	{
		\AppRoot::debug("setConfig(".$var.",".$value.")");
		if ($this->config == null)
			$this->fetch();

		if (is_array($value) || is_object($value))
			$value = json_encode($value);

		\MySQL::getDB()->updateinsert("system_config", array("var" => $var, "val" => $value), array("var" => $var));
		$this->config[$var] = $value;
	}

	function fetch()
	{
		$this->config = array();
		if ($results = \MySQL::getDB()->getRows("select var, val from system_config order by var")) {
			foreach ($results as $result) {
				$this->config[$result["var"]] = $result["val"];
                \AppRoot::debug("set-config: ".$result["var"]." = ".$result["val"]);
			}
		}

        // Zoek naar config overrides
        $directories = array("config");
        foreach ($directories as $directory) {
            $this->loadDirectory($directory);
        }
	}

    private function loadDirectory($directory)
    {
        \AppRoot::debug("config directory: " . $directory);
        foreach (\Tools::getFilesFromDirectory($directory) as $filename)
        {
            if (file_exists($filename)) {
                if (is_dir($filename)) {
                    $this->loadDirectory($filename);
                } else {
                    if (pathinfo($filename, PATHINFO_EXTENSION) == "conf") {
                        \AppRoot::debug("config file: [string]".$filename."[/string]");
                        foreach (file($filename) as $line)
                        {
                            if ($line[0] == "#")
                                continue;
                            $parts = explode("=", $line);
                            if (count($parts) > 1) {
                                $this->config[trim($parts[0])] = trim($parts[1]);
                                \AppRoot::debug("<b>override</b>-config: " . trim($parts[0]) . " = " . trim($parts[1]));
                            }
                        }
                    }
                }
            } else
                \AppRoot::debug("could not read config file: ".$filename, "red");
        }
    }


	/**
	 * Get config
	 * @return \Config
	 */
	public static function getCONFIG()
	{
		if (self::$configObject == null)
			self::$configObject = new \Config();

		return self::$configObject;
	}

	public static function setConfig($var, $value)
	{
		self::getCONFIG()->set($var, $value);
	}
}