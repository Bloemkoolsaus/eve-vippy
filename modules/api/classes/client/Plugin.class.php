<?php
namespace api\client
{
	class Plugin
	{
		/**
		 * @var SOAP
		 */
		protected $client;

		/**
		 * Get client
		 * @return SOAP
		 */
		public function getClient()
		{
			return $this->client;
		}

		/**
		 * Set client
		 * @param SOAP $client
		 */
		public function setClient($client)
		{
			$this->client = $client;
		}
	}
}