<?php
namespace api\client\plugin
{
	interface Call
	{
		/**
		 * Wrap a method call
		 * @param string $method Method to call
		 * @param array $parameters Parameters to use in method call
		 * @param callable $next Next closure to call
		 * @return mixed Result returned from closure
		 */
		function call($method, array $parameters, $next);
	}
}
?>