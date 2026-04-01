<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks;

class Memory extends \Traffic\Service\AbstractService
{
    private $_cache = [];
    public function set($scope, $key, $id)
    {
        $key = $this->_toKey($scope, $key);
        $this->_cache[$key] = $id;
        return $this->_cache[$key];
    }
    public function exists($scope, $key)
    {
        $key = $this->_toKey($scope, $key);
        return isset($this->_cache[$key]);
    }
    public function get($scope, $key)
    {
        $key = $this->_toKey($scope, $key);
        return isset($this->_cache[$key]) ? $this->_cache[$key] : NULL;
    }
    public function clean()
    {
        $this->_cache = [];
    }
    private function _toKey($scope, $key)
    {
        if (strstr($scope, "sub_id_")) {
            $scope = "sub_id";
        }
        return $scope . mb_strtolower($key, "utf-8");
    }
}

?>