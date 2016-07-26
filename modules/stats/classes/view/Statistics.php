<?php
namespace stats\view;

class Statistics
{
    function getOverview($arguments=[])
    {
        \AppRoot::title("Statistics");
        $console = new \stats\console\Stats();
        if (!\User::getUSER()->isAdmin())
            return \Tools::noRightMessage();

        $y = (count($arguments) > 0) ? array_shift($arguments) : date("Y");
        $m = (count($arguments) > 0) ? array_shift($arguments) : date("m");

        $sdate = date("Y-m-d", mktime(0, 0, 0, $m, 1, $y));
        $edate = date("Y-m-d", mktime(0, 0, 0, $m+1, 0, $y));

        $authGroups = \User::getUSER()->getAuthGroupsIDs();
        $authGroup = new \admin\model\AuthGroup($authGroups[0]);

        $corporations = array();
        foreach ($authGroup->getAllowedCorporations() as $corp)
        {
            $corporations[] = [
                "corp" => $corp,
                "users" => $console->getScannersByCorporationID($corp->id, $sdate, $edate)
            ];
        }

        $tpl = \SmartyTools::getSmarty();
        $tpl->assign("authGroup", $authGroup);
        $tpl->assign("corporations", $corporations);
        $tpl->assign("sdate", $sdate);
        $tpl->assign("edate", $edate);
        $tpl->assign("month", \Tools::getFullMonth(date("m",strtotime($sdate)))." ".date("Y",strtotime($sdate)));
        return $tpl->fetch("stats/overview");
    }
}