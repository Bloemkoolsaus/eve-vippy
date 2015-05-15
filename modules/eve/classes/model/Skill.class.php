<?php
namespace eve\model
{
	class Skill extends \eve\model\Item
	{

		/**
		 * Get the skill: Jump Drive Calibration
		 * Enter description here ...
		 */
		public static function getJumpDriveCalibration()
		{
			return new \eve\model\Skill(21611);
		}
	}
}
?>