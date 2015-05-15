<?php
namespace scanning\controller
{
	/*
	 *	LET OP!!! Wormhole is het wormhole systeem.
	 *  De daadwerkelijke wormhole (waar je door springt) is Connection
	 */
	class Wormhole
	{
		function getAddForm()
		{
			$errors = array();

			if (\Tools::REQUEST("from") && \Tools::REQUEST("to"))
			{
				if (\Tools::REQUEST("from") == \Tools::REQUEST("to"))
					return false;

				$fromSystem = \eve\model\SolarSystem::getSolarsystemByName(\Tools::REQUEST("from"));
				if ($fromSystem == null)
					$errors[] = "Solarsystem `".\Tools::REQUEST("from")."` not found.";

				$toSystem = \eve\model\SolarSystem::getSolarsystemByName(\Tools::REQUEST("to"));
				if ($toSystem == null)
					$errors[] = "Solarsystem `".\Tools::REQUEST("to")."` not found.";

				if (count($errors) == 0)
				{
					$chain = new \scanning\model\Chain(\User::getSelectedChain());
					$chain->id = \User::getSelectedChain();
					if ($chain->addWormholeSystem($fromSystem->id, $toSystem->id))
						return "added";
					else
						return false;
				}
			}


			$system = new \scanning\model\System(\User::getSelectedSystem());

			$tpl = \SmartyTools::getSmarty();
			$tpl->assign("system", $system);

			if (count($errors) > 0)
				$tpl->assign("errors", $errors);

			return $tpl->fetch("scanning/addwormhole");
		}
	}
}
?>