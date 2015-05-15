<?php
namespace eve\model
{
	class IGB
	{
		public static $igb = null;


		/**
		 * Is ingame browser?
		 * @return boolean
		 */
		function isIGB()
		{
			if (isset($_SERVER["HTTP_EVE_TRUSTED"]) && strlen(trim($_SERVER["HTTP_EVE_TRUSTED"])))
				return true;
			else
				return false;
		}

		/**
		 * is trusted?
		 * @return boolean
		 */
		function isTrusted()
		{
			if (isset($_SERVER["HTTP_EVE_TRUSTED"]) && strtolower(trim($_SERVER["HTTP_EVE_TRUSTED"])) == "yes")
				return true;
			else
				return false;
		}


		/**
		 * Get Solarsystem ID
		 * @return integer|boolean false
		 */
		function getSolarsystemID()
		{
			if (isset($_SERVER["HTTP_EVE_SOLARSYSTEMID"]))
				return $_SERVER["HTTP_EVE_SOLARSYSTEMID"];
			else
				return false;
		}
		/**
		 * Get Solarsystem Name
		 * @return string
		 */
		function getSolarsystemName()
		{
			if (isset($_SERVER["HTTP_EVE_SOLARSYSTEMNAME"]))
				return $_SERVER["HTTP_EVE_SOLARSYSTEMNAME"];
			else
				return "unkown";
		}


		/**
		 * Get Pilot Character ID
		 * @return integer|boolean false
		 */
		function getPilotID()
		{
			if (isset($_SERVER["HTTP_EVE_CHARID"]))
				return $_SERVER["HTTP_EVE_CHARID"];
			else
				return false;
		}
		/**
		 * Get pilot name
		 * @return string
		 */
		function getPilotName()
		{
			if (isset($_SERVER["HTTP_EVE_CHARNAME"]))
				return $_SERVER["HTTP_EVE_CHARNAME"];
			else
				return "unkown";
		}


		/**
		 * Get Corporation ID
		 * @return integer|boolean false
		 */
		function getCorporationID()
		{
			if (isset($_SERVER["HTTP_EVE_CORPID"]))
				return $_SERVER["HTTP_EVE_CORPID"];
			else
				return false;
		}
		/**
		 * Get Corporation name
		 * @return string
		 */
		function getCorporationName()
		{
			if (isset($_SERVER["HTTP_EVE_CORPNAME"]))
				return $_SERVER["HTTP_EVE_CORPNAME"];
			else
				return "unkown";
		}


		/**
		 * Get Alliance ID
		 * @return integer|boolean false
		 */
		function getAllianceID()
		{
			if (isset($_SERVER["HTTP_EVE_ALLIANCEID"]))
				return $_SERVER["HTTP_EVE_ALLIANCEID"];
			else
				return false;
		}
		/**
		 * Get Alliance name
		 * @return string
		 */
		function getAllianceName()
		{
			if (isset($_SERVER["HTTP_EVE_ALLIANCENAME"]))
				return $_SERVER["HTTP_EVE_ALLIANCENAME"];
			else
				return "unkown";
		}


		/**
		 * Get ship type ID
		 * @return integer|boolean false
		 */
		function getShiptypeID()
		{
			if (isset($_SERVER["HTTP_EVE_SHIPTYPEID"]))
				return $_SERVER["HTTP_EVE_SHIPTYPEID"];
			else
				return false;
		}
		/**
		 * Get Shiptype name
		 * @return string
		 */
		function getShiptypeName()
		{
			if (isset($_SERVER["HTTP_EVE_SHIPTYPENAME"]))
				return $_SERVER["HTTP_EVE_SHIPTYPENAME"];
			else
				return false;
		}



		/**
		 * Get ingame browser model
		 * @return \eve\model\IGB
		 */
		public static function getIGB()
		{
			if (self::$igb === null)
				self::$igb = new \eve\model\IGB();

			return self::$igb;
		}
	}
}
?>