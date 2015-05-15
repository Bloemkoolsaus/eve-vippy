<?php
namespace stats
{
	class Module extends \Module
	{
		public $moduleName = "stats";
		public $moduleTitle = "Statistics";


		function getContent()
		{
			$section = (\Tools::REQUEST("section")) ? \Tools::REQUEST("section") : "leaderboard";
			$action = (\Tools::REQUEST("action")) ? \Tools::REQUEST("action") : "overview";


			if ($section == "leaderboard")
			{
				$leaderboard = new \stats\view\Leaderboard();
				return $leaderboard->getOverview();
			}

			if ($section == "full")
			{
				$stats = new \stats\view\Statistics();
				return $stats->getOverview();
			}

			if ($section == "raffle")
			{
				$raffle = new \stats\view\Raffle();

				if (\Tools::REQUEST("ajax"))
				{
					if (\Tools::REQUEST("ticket"))
						return $raffle->rollTicket();
				}
				else
					return $raffle->getOverview();
			}


			return parent::getContent();
		}

		function getCron($arguments=array())
		{
			$action = "default";
			if (isset($arguments[0]))
				$action = $arguments[0];

			echo $action." ";



			return "unknown action";
		}
	}
}
?>