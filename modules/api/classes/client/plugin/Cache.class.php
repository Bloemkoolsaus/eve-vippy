<?php
namespace api\client\plugin
{
	class Cache extends \api\client\Plugin implements Raw
	{

		/**
		 * @var string
		 */
		protected $dir;

		/**
		 * @var callable
		 */
		protected $keyFunction;

		/**
		 * Constructor
		 *
		 * @param string $dir
		 * @param callable $keyFunction
		 */
		function __construct($dir, callable $keyFunction = null)
		{
			$this->dir = rtrim($dir, DIRECTORY_SEPARATOR);
			if (!file_exists($this->dir))
				mkdir($this->dir, 0777, true);

			$this->keyFunction = $keyFunction ?: function ($request)
			{
				return sha1(serialize($request));
			};
		}

		/**
		 * Wrap a raw request
		 *
		 * @param string $request Request to pass on to next closure
		 * @param callable $next Next closure to call
		 * @return string Response received from the closure
		 */
		function raw($request, callable $next)
		{
			$function = $this->keyFunction;
			$key      = $function($request);
			$filename = $this->dir . DIRECTORY_SEPARATOR . $key;
			if (file_exists($filename))
				return file_get_contents($filename);

			$response = $next($request);
			file_put_contents($filename, $response);

			return $response;
		}
	}
}
?>