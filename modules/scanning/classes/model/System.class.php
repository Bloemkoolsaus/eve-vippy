<?php
namespace scanning\model
{
	class System extends \eve\model\SolarSystem
	{
		function getTitleOnChain($chainID)
		{
			$systemName = "";
			if ($result = \MySQL::getDB()->getRow("SELECT * FROM mapwormholes WHERE solarsystemid = ? AND chainid = ?", array($this->id, $chainID)))
				$systemName = $result["name"];

			if (strlen(trim($systemName)) == 0)
			{
				if ($result = \MySQL::getDB()->getRow("SELECT * FROM mapwormholechains WHERE homesystemid = ?", array($this->id)))
					$systemName = $result["homesystemname"];
			}

			if (strlen(trim($systemName)) > 0)
				return $systemName;
			else
				return false;
		}

		function getCapitalRange($userID=null)
		{
			\AppRoot::debug("getCapitalRange($userID)", true);
			$capitals = array();

			// Moet wel lowsec of lager zijn.
			if (!$this->isWSpace() && $this->security <= 0.45)
			{
				if ($userID)
					$user = new \users\model\User($userID);
				else
					$user = \User::getUSER();

				foreach ($user->getCapitalShips() as $capital)
				{
					$cap = new \stdClass();
					$cap->ship = $capital->getShip();
					$cap->location = $capital->getSolarsystem();
					$cap->jumps = $capital->getNumberOfJumpsToSystem($this->id);
					$capitals[$cap->jumps][] = $cap;
				}
			}
			ksort($capitals);

			return $capitals;
		}

		function hasCapsInRange($userID=null)
		{
			// Unmapped system?
			if ($this->id == 0)
				return false;

			// Moet wel lowsec of lager zijn.
			if ($this->isWSpace() || $this->security > 0.45)
				return false;

			if ($userID)
				$user = new \users\model\User($userID);
			else
				$user = \User::getUSER();


			foreach ($user->getCapitalShips() as $capital)
			{
				if ($this->isSystemInJumpRange($capital->solarSystemID, $capital->getMaxJumprange()))
					return true;
			}
			return false;
		}

		function getActivePilots($mapID)
		{
			$pilots = array();
			$map = new \map\model\Map($mapID);
			$lastdate = date("Y-m-d H:i:s", mktime(date("H"),date("i")-5,date("s"),date("m"),date("d"),date("Y")));

			if ($results = \MySQL::getDB()->getRows("select	l.solarsystemid, l.lastdate,
															l.characterid, c.name,
													        l.shiptypeid, i.typeName as ship
													from	map_character_locations l
														inner join characters c on c.id = l.characterid
														left join ".\eve\Module::eveDB().".invtypes i on i.typeid = l.shiptypeid
													where	l.solarsystemid = ?
													and		l.lastdate > ?
													and		l.authgroupid = ?
													order by c.name"
									, [$this->id, $lastdate, $map->authgroupID]))
			{
				foreach ($results as $result) {
					$pilots[] = $result;
				}
			}
			return $pilots;
		}
	}
}
?>