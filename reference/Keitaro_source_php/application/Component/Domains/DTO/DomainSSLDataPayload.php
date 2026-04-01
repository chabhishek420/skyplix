<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Domains\DTO;

class DomainSSLDataPayload
{
    private $_checks = [];
    private $_total = 0;
    private $_allowedData = ["checks", "total"];
    public function __construct($data)
    {
        foreach ($data as $key => $value) {
            $name = "_" . \Traffic\Tools\Tools::toCamelCase($key, true);
            if (in_array($key, $this->_allowedData)) {
                $this->{$name} = $value;
            }
        }
    }
    public function checks()
    {
        return $this->_checks;
    }
    public function updateCheck()
    {
        $this->_checks[] = time();
    }
    public function setCheck($data)
    {
        $this->_checks = $data;
    }
    public function totalSSLAttempts()
    {
        return $this->_total;
    }
    public function updateTotal()
    {
        $this->_total += 1;
    }
    public function toArray()
    {
        return ["checks" => $this->checks(), "total" => $this->totalSSLAttempts()];
    }
}

?>