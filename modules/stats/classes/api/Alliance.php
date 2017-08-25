<?php
namespace stats\api;

class Alliance extends \api\Server
{
    function getDefault($arguments=[])
    {
        $alliance = new \eve\model\Alliance(array_shift($arguments));
        $year = (count($arguments) > 0) ? array_shift($arguments) : date("Y");
        $month = (count($arguments) > 0) ? array_shift($arguments) : date("m");

        $data = [
            "id" => $alliance->id,
            "name" => $alliance->name,
            "year" => $year,
            "month" => $month,
            "corporations" => []
        ];

        $corpAPI = new \stats\api\Corporation();
        foreach ($alliance->getCorporations() as $corp) {
            $data["corporations"][$corp->id] = $corpAPI->getDefault([$corp->id, $year, $month]);
        }

        return $data;
    }
}