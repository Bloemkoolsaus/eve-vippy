<?php
namespace api\client\plugin
{
	class LogRaw extends Log implements Raw
	{
		/**
		 * Log a raw request and its response or exception
		 * @param string $request
		 * @param callable $next
		 * @return string
		 * @throws \Exception
		 */
		function raw($request, callable $next)
		{
			$client   = $this->getClient();
			$location = $this->formatLocation($client);

			try
			{
				$response = $next($request);

				$this->log($location, array(
					"Request"  => $this->formatHeaders($client->__getLastRequestHeaders()) . $this->formatXML($request),
					"Response" => $this->formatHeaders($client->__getLastResponseHeaders()) . $this->formatXML($response),
				));

				return $response;
			}
			catch (\Exception $e)
			{
				$this->log($location, array(
					"Request"   => $this->formatHeaders($client->__getLastRequestHeaders()) . $this->formatXML($request),
					"Exception" => $this->formatException($e),
				));

				throw $e;
			}
		}
	}
}
?>