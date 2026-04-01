<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Device\Service;

class RealRemoteIpService extends \Traffic\Service\AbstractService
{
    const LOCAL_MASK_1 = "192.168.";
    const LOCAL_MASK_2 = "127.0.";
    public function find(\Traffic\Request\ServerRequest $request)
    {
        $possibleRealIps = [$this->_ipFromOperaMini($request), $this->_ipFromXRealIp($request), $this->_ipFromXForwardedFor($request), $this->_ipFromForwarded($request), $this->_ipFromCfConnectingIp($request), $this->_ipFromRemoteAddr($request), $this->_lastHope($request)];
        foreach ($possibleRealIps as $ip) {
            if ($this->_isCorrectIpForHeader($ip)) {
                return $ip;
            }
        }
        return $ip;
    }
    private function _ipFromOperaMini(\Traffic\Request\ServerRequest $serverRequest)
    {
        if (stristr($serverRequest->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_USER_AGENT), "mini")) {
            $tmp = explode(",", $serverRequest->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_X_FORWARDED_FOR));
            return trim($tmp[count($tmp) - 2]);
        }
        return NULL;
    }
    private function _ipFromXRealIp(\Traffic\Request\ServerRequest $serverRequest)
    {
        return $serverRequest->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_X_REAL_IP);
    }
    private function _ipFromXForwardedFor(\Traffic\Request\ServerRequest $serverRequest)
    {
        $ips = $serverRequest->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_X_FORWARDED_FOR);
        $ips = explode(",", $ips);
        return 1 < count($ips) ? $ips[0] : NULL;
    }
    private function _ipFromForwarded(\Traffic\Request\ServerRequest $serverRequest)
    {
        $ips = str_replace("for=", "", $serverRequest->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_FORWARDED));
        $ips = explode(",", $ips);
        return $ips[0];
    }
    private function _ipFromRemoteAddr(\Traffic\Request\ServerRequest $serverRequest)
    {
        return $serverRequest->getServerParam(\Traffic\Request\ServerRequest::REMOTE_ADDR);
    }
    private function _ipFromCfConnectingIp(\Traffic\Request\ServerRequest $serverRequest)
    {
        return $serverRequest->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_CF_CONNECTING_IP);
    }
    private function _lastHope(\Traffic\Request\ServerRequest $serverRequest)
    {
        $ips = $serverRequest->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_X_FORWARDED_FOR);
        $ips = explode(",", $ips);
        return $ips[0];
    }
    private function _isCorrectIpForHeader($ip)
    {
        if (empty($ip)) {
            return false;
        }
        if (strpos($ip, LOCAL_MASK_1) === 0 || strpos($ip, LOCAL_MASK_2) === 0) {
            return false;
        }
        return true;
    }
}

?>