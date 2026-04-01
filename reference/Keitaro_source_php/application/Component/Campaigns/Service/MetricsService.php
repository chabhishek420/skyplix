<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Campaigns\Service;

class MetricsService extends \Traffic\Service\AbstractService
{
    public static function instance()
    {
        return self::instance();
    }
    public function calculateRoi($revenue, $cost)
    {
        if ($cost == 0) {
            if (0 < $revenue) {
                return 100;
            }
            return 0;
        }
        return ceil(($revenue - $cost) / $cost * 100);
    }
    public function calculateMean($value, $count)
    {
        if ($count == 0) {
            return 0;
        }
        return round((int) $value / (int) $count, 2);
    }
    public function calculateEcpm($value, $count)
    {
        if ($count == 0) {
            return 0;
        }
        return round((int) $value / (int) $count * 1000, 2);
    }
    public function calculateConversion($leads, $count)
    {
        if (0 < $count) {
            $result = (int) $leads / (int) $count * 100;
        } else {
            $result = 0;
        }
        return round($result, 2);
    }
}

?>