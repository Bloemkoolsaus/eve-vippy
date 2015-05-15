<?php
namespace profile\controller
{
	class Capitals
	{
		function getOverviewSection()
		{
			$section = new \Section("profile_capitals","id");

			$section->addElement("Ship", "shipid", false, 'eve\elements\CapitalShip');
			$section->addElement("Location", "solarsystemid", false, 'eve\elements\SolarSystem');
			$section->addElement("Description", "description");

			$section->whereQuery = " WHERE userid = ".\User::getUSER()->id;
			$section->addStaticField("userid", \User::getUSER()->id);

			$section->allowEdit = true;
			$section->allowSearch = false;

			return $section;
		}

		function deleteCapitalShip($id)
		{
			\MySQL::getDB()->delete("profile_capitals", array("id" => $id));
		}
	}
}
?>