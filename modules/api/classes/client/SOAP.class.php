<?php
namespace api\client
{
	/**
	 * SOAP client with default wsdl/options and plugin support
	 *
	 * WSDL and options can be specified as members in subclasses or overridden by passing
	 * them to the constructor. Plugins can be added using the constructor or the addPlugin
	 * method.
	 *
	 * Each plugin wraps the existing client and its plugins like an extra outside shell.
	 * When you call a soap method and when you perform a raw request, you actually let the
	 * outermost plugin/shell perform this call or request. Each plugin then has the ability
	 * to observe (log?) and/or modify (sign?) the request before passing it on to the next
	 * inner plugin/shell. It even has the possibility to handle the call/request itself
	 * (cache?) and not defer further handling to the next inner plugin.
	 *
	 * When the request reaches the SOAP client, it performs the actual call as it normally
	 * does and returns the response to the innermost plugin/shell. The response then
	 * travels back in reverse order to the caller.
	 *
	 * Because a SOAP call is a 2-phase process, it actually does this process twice: once
	 * for the call (__soapCall) and then inside the call for the raw request (__doRequest).
	 *
	 * This can be visualized like this:
	 *                      /              /
	 *    __soapCall -->-- / ----->------ / ---->--+ PHP SOAP client receives __soapCall and emits __doRequest with raw XML
	 *                    /              /         |
	 * +-------------------------------------------+
	 * |                 /              /
	 * +- __doRequest -> | ------->--- | ------->--+ PHP SOAP client receives __doRequest and sends XML request to the server
	 *                   |             |           |
	 * raw result   +-<- | ------<---- | ------<---+ XML Response from server received and returned
	 *              |    \             \
	 *              +------------------------------+
	 *                    \             \          |
	 * call result    <--- \ -----<----- \ ----<---+ XML response received and returned as objects
	 *                      \             \
	 *                   plugin 1      plugin 2
	 *
	 * Each plugin can opt-in to encapsulate either the call or the raw request (or both)
	 * by implementing the Raw and/or Call interfaces.
	 *
	 * Beware that the order of adding multiple plugins that operate on the same level (raw or call) matters:
	 * - If you add the LogRaw plugin first and then the Signature plugin, you will log the signed requests
	 * - If you add the Signature plugin first and the LogRaw plugin next, you will log unsigned requests
	 * - If you add the LogRaw plugin first and the Cache plugin next, only actual requests to the server will be logged
	 * - If you add the Cache plugin first and the LogRaw plugin next, all requests will be logged
	 */
	class SOAP extends \SoapClient
	{
		/**
		 * Default wsdl
		 * @var string
		 */
		public $wsdl = null;

		/**
		 * Default options
		 * @var array
		 */
		public $options = array();

		/**
		 * Plugins
		 * @var Plugin[]
		 */
		protected $plugins = array();

		/**
		 * Constructor
		 * @param string $wsdl
		 * @param array $options
		 * @param array $plugins
		 */
		function __construct($wsdl = null, array $options = null, array $plugins = null)
		{
			if ($wsdl)
				$this->wsdl = $wsdl;
			if ($options)
				$this->options = array_merge($this->options, $options);
			if ($plugins)
				$this->setPlugins(array_merge($this->plugins, $plugins));

			parent::__construct($this->wsdl, $this->options);
		}

		/**
		 * Get WSDL
		 * @return string
		 */
		function getWSDL()
		{
			return $this->wsdl;
		}

		/**
		 * Get options
		 * @return array
		 */
		function getOptions()
		{
			return $this->options;
		}

		/**
		 * Get option value
		 * @param string $option
		 * @param mixed $default
		 * @return mixed
		 */
		function getOption($option, $default = null)
		{
			if (array_key_exists($option, $this->options))
				return $this->options[$option];

			return $default;
		}

		/**
		 * Get plugins
		 * @return Plugin[]
		 */
		function getPlugins()
		{
			return $this->plugins;
		}

		/**
		 * Get a plugin by name
		 * @param $name
		 * @return Plugin
		 */
		function getPlugin($name)
		{
			if (!isset($this->plugins[$name]))
				throw new \RuntimeException("SOAP client instance doesn't have this plugin: " . $name);

			return $this->plugins[$name];
		}

		/**
		 * Set plugins
		 * @param Plugin[]|array $plugins Plugins array with keys matching their class name in the api\client\plugin namespace
		 */
		function setPlugins($plugins)
		{
			$this->plugins = array();
			foreach ($plugins as $name => $plugin)
				$this->setPlugin($name, $plugin);
		}

		/**
		 * Set a plugin
		 * @param string $name Name matching the class in the api\client\plugin namespace
		 * @param Plugin|array $plugin Plugin instance or constructor arguments
		 */
		function setPlugin($name, $plugin = null)
		{
			if (!is_object($plugin))
			{
				$class  = new \ReflectionClass("api\\client\\plugin\\$name");
				$plugin = $class->newInstanceArgs($plugin);
			}

			$this->plugins[$name] = $plugin;
			$this->plugins[$name]->setClient($this);
		}

		/**
		 * Calls a SOAP method trough plugins
		 * @link http://php.net/manual/en/soapclient.soapcall.php
		 * @param string $function_name The name of the SOAP function to call.
		 * @param array $arguments An array of the arguments to pass to the function.
		 * @param array $options An associative array of options to pass to the client.
		 * @param mixed $input_headers An array of headers to be sent along with the SOAP request.
		 * @param array $output_headers If supplied, this array will be filled with the headers from the SOAP response.
		 * @return mixed
		 */
		public function __soapCall($function_name, $arguments, $options = NULL, $input_headers = NULL, &$output_headers = NULL)
		{
			// Create the innermost closure that performs the call
			$client  = $this;
			$closure = function($function_name, $arguments) use ($client, $options, $input_headers, $output_headers) {
				return $client->__soapCallOriginal($function_name, $arguments, $options, $input_headers, $output_headers);
			};

			// Each plugin wraps the closure before with a new one
			foreach ($this->plugins as $plugin)
			{
				if ($plugin instanceof \api\client\plugin\Call)
				{
					$closure = function ($method, $arguments) use ($plugin, $closure)
					{
						return $plugin->call($method, $arguments, $closure);
					};
				}
			}

			// Now call the outermost closure and let it propagate the call by itself
			return $closure($function_name, $arguments);
		}

		/**
		 * Performs a raw SOAP request through plugins
		 * @link http://php.net/manual/en/soapclient.dorequest.php
		 * @param string $request The XML SOAP request.
		 * @param string $location The URL to request.
		 * @param string $action The SOAP action.
		 * @param int $version The SOAP version.
		 * @param int $oneWay If oneWay is set to 1, this method returns nothing.
		 * @return string The XML SOAP response.
		 */
		public function __doRequest($request, $location, $action, $version, $oneWay = 0)
		{
			// Create the innermost closure that performs the call
			$client  = $this;
			$closure = function($request) use ($client, $location, $action, $version, $oneWay) {
				return $client->__doRequestOriginal($request, $location, $action, $version, $oneWay);
			};

			// Each plugin wraps the closure before with a new one
			foreach ($this->plugins as $plugin)
			{
				if ($plugin instanceof \api\client\plugin\Raw)
				{
					$closure = function ($request) use ($plugin, $closure)
					{
						return $plugin->raw($request, $closure);
					};
				}
			}

			// Now call the outermost closure and let it propagate the call by itself
			return $closure($request);
		}

		/**
		 * Calls a SOAP method WITHOUT plugins
		 * @link http://php.net/manual/en/soapclient.soapcall.php
		 * @param string $function_name The name of the SOAP function to call.
		 * @param array $arguments An array of the arguments to pass to the function.
		 * @param array $options An associative array of options to pass to the client.
		 * @param mixed $input_headers An array of headers to be sent along with the SOAP request.
		 * @param array $output_headers If supplied, this array will be filled with the headers from the SOAP response.
		 * @return mixed
		 */
		public function __soapCallOriginal($function_name, $arguments, $options = NULL, $input_headers = NULL, &$output_headers = NULL)
		{
			return parent::__soapCall($function_name, $arguments, $options, $input_headers, $output_headers);
		}

		/**
		 * Performs a raw SOAP request WITHOUT plugins
		 * @link http://php.net/manual/en/soapclient.dorequest.php
		 * @param string $request The XML SOAP request.
		 * @param string $location The URL to request.
		 * @param string $action The SOAP action.
		 * @param int $version The SOAP version.
		 * @param int $oneWay If oneWay is set to 1, this method returns nothing.
		 * @return string The XML SOAP response.
		 */
		public function __doRequestOriginal($request, $location, $action, $version, $oneWay = 0)
		{
			return parent::__doRequest($request, $location, $action, $version, $oneWay);
		}

		/**
		 * Get SOAP client
		 * @deprecated Use this object directly instead
		 * @return \SoapClient
		 */
		function getClient()
		{
			\AppRoot::depricatedLog(__METHOD__);

			return $this;
		}

		/**
		 * Do request
		 * @deprecated Use $this->__soapCall($method, $vars) or $this->method(...) directly instead
		 * @param string $request
		 * @param mixed $var
		 * @return mixed
		 */
		function doRequest($request, $var)
		{
			\AppRoot::depricatedLog(__METHOD__);

			return $this->__soapCall($request, array($var));
		}
	}
}
?>