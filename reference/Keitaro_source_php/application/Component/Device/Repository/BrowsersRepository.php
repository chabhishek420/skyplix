<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Device\Repository;

class BrowsersRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_matches = ["edge" => "edge", "internet explorer" => "ie", "chrome" => "chrome", "firefox" => "firefox", "opera" => "opera", "safari" => "safari", "yandex" => "yandex"];
    public function getBrowsers()
    {
        $browsers = (include ROOT . "/application/Component/Device/dictionaries/browsers.php");
        sort($browsers);
        return $browsers;
    }
    public function getCode($name)
    {
        foreach ($this->_matches as $match => $code) {
            if (stristr($name, $match)) {
                return $code;
            }
        }
        return NULL;
    }
}

?>