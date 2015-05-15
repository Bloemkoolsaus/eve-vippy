<?php
namespace users
{
	class Profile
	{
		public static function getProfile()
		{
			$section = User::getOverviewSection();
			$section->keyvalue = \User::getUSER()->id;
			return $section->getEditForm();
		}
	}
}
?>