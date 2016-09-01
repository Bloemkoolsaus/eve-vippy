<?php
namespace api\client\plugin
{
	interface Raw
	{
		/**
		 * Wrap a raw request
		 * @param string $request Request to pass on to next closure
		 * @param callable $next Next closure to call
		 * @return string Response received from the closure
		 */
		function raw($request, callable $next);
	}
}
?>