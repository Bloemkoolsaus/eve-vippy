<?php
namespace profile\controller
{
	class Capitals
	{

		function deleteCapitalShip($id)
		{
			\MySQL::getDB()->delete("profile_capitals", array("id" => $id));
		}
	}
}
?>