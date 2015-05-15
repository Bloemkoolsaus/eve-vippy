<?php
namespace profile\model
{
	class Capital
	{
		public $id = 0;
		public $userID;
		public $shipID;
		public $solarSystemID;
		public $description;

		private $ship = null;
		private $user = null;
		private $system = null;

		function __construct($id=false)
		{
			if ($id) {
				$this->id = $id;
				$this->load();
			}
		}

		function load($result=false)
		{
			if (!$result)
				$result = \MySQL::getDB()->getRow("SELECT * FROM profile_capitals WHERE id = ?", array($this->id));

			if ($result)
			{
				$this->id = $result["id"];
				$this->userID = $result["userid"];
				$this->shipID = $result["shipid"];
				$this->solarSystemID = $result["solarsystemid"];
				$this->description = $result["description"];
			}
		}

		/**
		 * Get number of jumps to a certain system
		 * @param itneger $solarSystemID
		 * @return number
		 */
		function getNumberOfJumpsToSystem($solarSystemID)
		{
			$nrJumps = $this->getSolarsystem()->getNrCapitalJumps($solarSystemID, $this->getMaxJumprange());
			\AppRoot::debug("getNumberOfJumpsToSystem($solarSystemID): ".$nrJumps);
			return $nrJumps;
		}

		/**
		 * Get max jumprange
		 * @param string $jumpDriveCalibrationLevel
		 * @return number
		 */
		function getMaxJumprange($jumpDriveCalibrationLevel=false)
		{
			if (!$jumpDriveCalibrationLevel)
				$jumpDriveCalibrationLevel = $this->getUser()->getSetting("jumpdrivecal");

			if (!$jumpDriveCalibrationLevel)
				$jumpDriveCalibrationLevel = 4;

			return $this->getShip()->getMaxJumprange($jumpDriveCalibrationLevel);
		}

		/**
		 * Get ship
		 * @return \eve\model\Ship
		 */
		function getShip()
		{
			if ($this->ship == null)
				$this->ship = new \eve\model\Ship($this->shipID);

			return $this->ship;
		}

		/**
		 * Get user
		 * @return \users\model\User
		 */
		function getUser()
		{
			if ($this->user == null)
				$this->user = new \users\model\User($this->userID);

			return $this->user;
		}

		/**
		 * Get solarsystem
		 * @return \eve\model\SolarSystem
		 */
		function getSolarsystem()
		{
			if ($this->system == null)
				$this->system = new \eve\model\SolarSystem($this->solarSystemID);

			return $this->system;
		}






		/**
		 * Get capital ships by user
		 * @param integer $userID
		 * @return \profile\model\Capital[]
		 */
		public static function getCapitalShipsByUser($userID)
		{
			$ships = array();
			if ($results = \MySQL::getDB()->getRows("SELECT * FROM profile_capitals WHERE userid = ?", array($userID)))
			{
				foreach ($results as $result)
				{
					$cap = new \profile\model\Capital();
					$cap->load($result);
					$ships[] = $cap;
				}
			}
			return $ships;
		}
	}
}
?>