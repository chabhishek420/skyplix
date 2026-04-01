<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Device\Repository;

class OsRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_matches = ["windows" => "windows", "android" => "android", "chrome" => "chrome", "debian" => "debian", "ios" => "ios", "os x" => "osx", "ubuntu" => "ubuntu"];
    public function getOs()
    {
        return array_values(include ROOT . "/application/Component/Device/dictionaries/os.php");
    }
    public function getCode($name)
    {
        foreach ($this->_matches as $match => $code) {
            if (stristr($name, $match)) {
                return $code;
            }
        }
    }
}

?>