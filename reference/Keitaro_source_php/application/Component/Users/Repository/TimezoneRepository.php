<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Repository;

class TimezoneRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_timezones = NULL;
    public function __construct()
    {
        foreach (\DateTimeZone::listIdentifiers() as $tz) {
            $time = new \DateTime("now", new \DateTimeZone($tz));
            $name = "(GMT" . $time->format("P") . ") " . $tz;
            $this->_timezones[$name] = $tz;
        }
        ksort($this->_timezones);
    }
    public function listAsOptions()
    {
        $items = [];
        foreach ($this->_timezones as $name => $timezone) {
            $items[] = ["value" => $timezone, "name" => $name];
        }
        return $items;
    }
    public function getOffset($tzName)
    {
        $time = new \DateTime("now", new \DateTimeZone($tzName));
        return $time->format("P");
    }
}

?>