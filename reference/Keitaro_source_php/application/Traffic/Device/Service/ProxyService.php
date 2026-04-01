<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Device\Service;

class ProxyService extends \Traffic\Service\AbstractService
{
    private $_forceNoProxy = false;
    private $_proxyHeaders = NULL;
    private $_proxyHeadersCheck = ["X-YANDEX-TURBO"];
    private $_cloudFlareHeaders = NULL;
    const LOCALHOST_IP = "127.0.0.1";
    public function forceNoProxy($value = true)
    {
        $this->_forceNoProxy = $value;
    }
    public function usingProxy(\Traffic\Request\ServerRequest $request)
    {
        if ($this->_forceNoProxy) {
            return false;
        }
        $ip = $request->getServerParam(\Traffic\Request\ServerRequest::ORIGINAL_REMOTE_ADDR);
        if ($this->_isBehindCloudFlare($request) && $this->_isXffContainsCfcip($request)) {
            if ($this->_isBehindCloudFlare($request) && !$this->_isXffContainsCfcip($request)) {
                if ($this->_isBehindLocalProxy($ip)) {
                    return $this->_detectProxyUsageByHeaders($request, $ip);
                }
                return $this->_hasSeveralIpsInXffHeader($request);
            }
            return false;
        }
        return $this->_hasSeveralIpsInXffHeader($request);
    }
    private function _isBehindLocalProxy($ip)
    {
        return $ip == LOCALHOST_IP;
    }
    private function _isBehindCloudFlare(\Traffic\Request\ServerRequest $request)
    {
        foreach ($this->_cloudFlareHeaders as $header) {
            if ($request->hasHeader($header)) {
                return true;
            }
        }
        return false;
    }
    private function _isXffContainsCfcip(\Traffic\Request\ServerRequest $request)
    {
        if ($request->hasHeader(\Traffic\Request\ServerRequest::HEADER_X_FORWARDED_FOR) && $request->hasHeader(\Traffic\Request\ServerRequest::HEADER_CF_CONNECTING_IP) && $request->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_X_FORWARDED_FOR) && $request->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_CF_CONNECTING_IP)) {
            $ipsList = array_map("trim", explode(",", $request->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_X_FORWARDED_FOR)));
            return in_array($request->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_CF_CONNECTING_IP), $ipsList);
        }
        return false;
    }
    private function _hasSeveralIpsInXffHeader(\Traffic\Request\ServerRequest $request)
    {
        $ipsList = array_map("trim", explode(",", $request->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_X_FORWARDED_FOR)));
        return 1 < sizeof(array_unique($ipsList));
    }
    private function _detectProxyUsageByHeaders(\Traffic\Request\ServerRequest $request, $ip)
    {
        foreach ($this->_proxyHeadersCheck as $header) {
            if ($request->getHeaderLine($header)) {
                return true;
            }
        }
        foreach ($this->_proxyHeaders as $header) {
            if ($request->getHeaderLine($header) && $request->getHeaderLine($header) != $ip) {
                return true;
            }
        }
        return false;
    }
}

?>