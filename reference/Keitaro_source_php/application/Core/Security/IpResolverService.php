<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Security;

class IpResolverService extends \Traffic\Service\AbstractService
{
    const DOMAIN = "domain";
    const LOCAL_IP = "127.0.0.1";
    public function findLicenseIp()
    {
        $resolveMethod = \Traffic\Service\ConfigService::instance()->get("system", "resolve_method");
        if ($this->_isValidIp($resolveMethod)) {
            return $resolveMethod;
        }
        if ($resolveMethod == DOMAIN) {
            $order = ["Domain", "Server"];
        } else {
            $order = ["Server", "Domain"];
        }
        $ip = NULL;
        foreach ($order as $method) {
            $ip = call_user_func([$this, "get" . $method . "Ip"]);
            if ($this->_isValidIp($ip)) {
                return $ip;
            }
        }
        $ip = $this->_getCachedLicenseIp();
        if (empty($ip)) {
            return LOCAL_IP;
        }
        return $ip;
    }
    private function _isValidIp($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }
        return strpos($ip, "192.168.") !== 0 && strpos($ip, "127.") !== 0;
    }
    public function getServerIp()
    {
        $ip = isset($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_ADDR"] : LOCAL_IP;
        $ip = str_replace("::ffff:", "", $ip);
        return $ip;
    }
    public function getDomainIp()
    {
        if (!empty($_SERVER["HTTP_HOST"])) {
            return gethostbyname($_SERVER["HTTP_HOST"]);
        }
        return NULL;
    }
    public function cacheLicenseIp($ip)
    {
        if (!empty($ip)) {
            $binaryIp = @inet_pton($ip);
            if (!empty($binaryIp)) {
                @file_put_contents(@$this->_getIpCacheFilePath(), $binaryIp);
            }
        }
    }
    private function _getCachedLicenseIp()
    {
        $ip = NULL;
        if (empty($ip)) {
            $binaryIp = @file_get_contents(@$this->_getIpCacheFilePath());
            if (!empty($binaryIp)) {
                $ip = @inet_ntop($binaryIp);
                if (!empty($ip)) {
                    return $ip;
                }
            }
        }
        return $ip;
    }
    private function _getIpCacheFilePath()
    {
        return ROOT . "/var/license/cache.lic";
    }
}

?>