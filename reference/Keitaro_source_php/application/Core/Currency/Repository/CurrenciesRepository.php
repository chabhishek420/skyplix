<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Currency\Repository;

class CurrenciesRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_data = NULL;
    private $_list = NULL;
    const RUR = "RUR";
    const RUB = "RUB";
    public function getData()
    {
        if (!$this->_data) {
            $this->_data = (include __DIR__ . "/../data/currencies.php");
        }
        return $this->_data;
    }
    public function getCurrencies()
    {
        if (!$this->_list) {
            $items = $this->getData();
            $result = [];
            foreach ($items as $name => $symbol) {
                $result[] = ["value" => $name, "name" => $name . " (" . $symbol . ")"];
            }
            $this->_list = $result;
        }
        return $this->_list;
    }
    public function getSymbol($currency)
    {
        $currencies = $this->getData();
        return $currencies[$currency];
    }
}

?>