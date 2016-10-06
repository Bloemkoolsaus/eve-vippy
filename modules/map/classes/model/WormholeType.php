<?php
namespace map\model;

class WormholeType extends \scanning\model\WormholeType
{


    /**
     * Find static types by solarsystem
     * @param $solarSystemID
     * @return \map\model\WormholeType[]
     */
    public static function findStaticBySolarSystem($solarSystemID)
    {
        $statics = [];
        if ($results = \MySQL::getDB()->getRows("select t.*
                                                from    mapwormholetypes t
                                                    inner join mapwormholestatics s on s.whtypeid = t.id
                                                where   s.solarsystemid = ?
                                                order by t.destination desc"
                                        , [$solarSystemID]))
        {
            foreach ($results as $result)
            {
                $whType = new \map\model\WormholeType();
                $whType->load($result);
                $statics[] = $whType;
            }
        }
        return $statics;
    }
}