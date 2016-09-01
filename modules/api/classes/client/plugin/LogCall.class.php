<?php
namespace api\client\plugin
{
	class LogCall extends Log implements Call
	{
		/**
		 * Wrap a method call and its result or exception
		 * @param string $method Method to call
		 * @param array $parameters Parameters to use in method call
		 * @param callable $next Next closure to call
		 * @return mixed Result returned from closure
		 * @throws \Exception
		 */
		function call($method, array $parameters, $next)
		{
			try
			{
				$result = $next($method, $parameters);

				$this->log($method, array(
					"Parameters" => $this->formatVar($parameters),
					"Result"     => $this->formatVar($result),
				));

				return $result;
			}
			catch (\Exception $e)
			{
				$this->log($method, array(
					"Parameters" => $this->formatVar($parameters),
					"Exception"  => $this->formatException($e),
				));

				throw $e;
			}
		}
	}
}
?>