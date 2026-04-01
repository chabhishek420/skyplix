<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection;

class CheckInList
{
    private $_list = NULL;
    public function __construct($list)
    {
        $this->_list = $list;
    }
    public function call($ip)
    {
        $m = "_v" . $this->getVersion();
        if (!method_exists($this, $m)) {
            $m = "_v0";
        }
        return call_user_func([$this, $m], $ip);
    }
    public function getVersion()
    {
        if (strstr($this->_list, "#version") && preg_match("/^#version:([0-9]+)\$/", strtok($this->_list, "\n"), $match)) {
            return $match[1];
        }
        return 0;
    }
    private function _v0($ip)
    {
        $ipInt = ip2long($ip);
        $lines = explode("\n", $this->_list);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === (int) (int) $line) {
                $line = (int) $line;
            }
            if ($line) {
                if ($line === $ipInt || $line == $ip) {
                    return true;
                }
                if (@strstr($line, "/") && \Traffic\Tools\Tools::ipInCIDR($ip, $line)) {
                    return true;
                }
            }
        }
        return false;
    }
    private function _v1($ip)
    {
        return preg_match("/" . str_replace(".", "\\.", $ip) . "(\$|\n)/s", $this->_list);
    }
    private function _v2($ua)
    {
        strtok($this->_list, "\n");
        while ($signature = strtok("\n")) {
            if (stristr($ua, $signature)) {
                return true;
            }
        }
        return false;
    }
}

?>