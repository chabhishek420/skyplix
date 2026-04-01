<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class TrafficSourceName extends \Traffic\Macros\AbstractClickMacro
{
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\RawClick $rawClick)
    {
        while ($rawClick->getTsId()) {
            try {
                $ts = \Traffic\Repository\CachedTrafficSourceRepository::instance()->findCached($rawClick->getTsId());
                if ($ts) {
                    return $ts->getName();
                }
            } catch (\Traffic\Cache\NoCache $e) {
                return "not_found_ts_name";
            }
        }
        return "";
    }
}

?>