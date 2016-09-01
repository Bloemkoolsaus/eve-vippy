<?php
namespace api\client\plugin
{
	abstract class Log extends \api\client\Plugin
	{
		/**
		 * Path to xmllint
		 */
		const XMLLINT_PATH = '/usr/bin/xmllint';

		/**
		 * @var string
		 */
		protected $log;

		/**
		 * @var string
		 */
		protected $dir;

		/**
		 * @var bool
		 */
		protected $prettify;

		/**
		 * Constructor
		 * @param string $log Log filename (without extension)
		 * @param string $dir Directory within the logs folder (defaults to api/client)
		 * @param bool $prettify Prettify? (requires the libxml2-utils package to format XML)
		 */
		public function __construct($log, $dir = "api/client", $prettify = false)
		{
			$this->log      = $log;
			$this->dir      = $dir;
			$this->prettify = $prettify;
		}

		/**
		 * Log data
		 * @param string $line
		 * @param array $data
		 */
		protected function log($line, array $data = null)
		{
			\AppRoot::addToLog($this->format($line, $data), $this->log, $this->dir, null);
		}

		/**
		 * Format message
		 * @param string $line
		 * @param array $data
		 * @return string
		 */
		protected function format($line, array $data = null)
		{
			$out = $line;
			if ($data)
				$out .= PHP_EOL . PHP_EOL;

			foreach ($data as $title => $body)
			{
				$out .= $title . PHP_EOL
					. str_repeat('-', strlen($title)) . PHP_EOL
					. $body . PHP_EOL
					. PHP_EOL;
			}

			return $out;
		}

		/**
		 * Formatted location description
		 *
		 * @param \api\client\SOAP $client
		 * @return string
		 */
		protected function formatLocation(\api\client\SOAP $client)
		{
			$location = $client->getOption("location");
			if (!$location)
				return "";

			$out = parse_url($location, PHP_URL_SCHEME) . "://";
			if ($client->getOption("login") && $client->getOption("password"))
				$out .= $client->getOption("login") . ":" . $client->getOption("password") . "@";
			$out .= parse_url($location, PHP_URL_HOST);
			if (parse_url($location, PHP_URL_PORT))
				$out .= ":" . parse_url($location, PHP_URL_PORT);

			return $out;
		}

		/**
		 * Format variable
		 * @param $var
		 * @return string
		 */
		protected function formatVar($var)
		{
			$export = var_export($var, true);
			if (!$this->prettify) {
				return $export;
			}

			$replacements = array(
				"/ =>\\s+/s" => " => ", // Put key and class::__set_state( call on same line
				"/^( *) '/m" => "\\1'", // Fix alignment of associative array keys
			);
			foreach ($replacements as $search => $replace)
				$export = preg_replace($search, $replace, $export);

			return $export;
		}

		/**
		 * Format XML
		 * @param string $xml
		 * @return string
		 */
		protected function formatXML($xml)
		{
			if ($xml === null)
				return "NULL";
			if (!$this->prettify)
				return trim($xml);

			$command     = self::XMLLINT_PATH . ' --format -';
			$descriptors = array(0 => array("pipe", "r"), 1 => array("pipe", "w"));
			$process     = proc_open($command, $descriptors, $pipes);
			if (!is_resource($process))
				throw new \RuntimeException("Could not open process: " . $command);

			fwrite($pipes[0], $xml);
			fclose($pipes[0]);

			$result = stream_get_contents($pipes[1]);
			fclose($pipes[1]);

			$exit = proc_close($process);
			if ($exit)
				throw new \RuntimeException("xmllint returned exit code " . $exit);

			return trim($result);
		}

		/**
		 * Format headers
		 * @param string $headers
		 * @return string
		 */
		protected function formatHeaders($headers)
		{
			$out = trim($headers);
			if ($out)
				$out .= PHP_EOL . PHP_EOL;

			return $out;
		}

		/**
		 * Format exception
		 * @param \Exception $exception
		 * @return string
		 */
		protected function formatException(\Exception $exception)
		{
			$out = get_class($exception) . ": " . $exception->getMessage() . " (" . $exception->getCode() . ")" . PHP_EOL
				 . $exception->getTraceAsString();

			return $out;
		}
	}
}
?>