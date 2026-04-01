<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Predefined;

class DoubleMeta extends \Traffic\Actions\AbstractAction
{
    protected $_weight = 3;
    protected function _execute()
    {
        $this->_executeInContext();
    }
    protected function _executeDefault()
    {
        $gatewayUrl = $this->_getGatewayUrl($this->getActionPayload());
        $code = \Traffic\Actions\Service\RedirectService::instance()->metaRedirect($gatewayUrl);
        $this->setContent($code);
        $this->setDestinationInfo($this->getActionPayload());
    }
    protected function _executeForFrame()
    {
        $url = $this->_getGatewayUrl($this->getActionPayload());
        $this->setContent(\Traffic\Actions\Service\RedirectService::instance()->frameRedirect($url));
    }
    protected function _executeForScript()
    {
        $this->setContentType("application/javascript");
        $url = $this->_getGatewayUrl($this->getActionPayload());
        $this->setContent(\Traffic\Actions\Service\RedirectService::instance()->scriptRedirect($url));
    }
    private function _getGatewayUrl($url)
    {
        $token = $this->_getToken($url);
        return $this->_getGatewayBaseUrl() . "?frm=dm&token=" . $token;
    }
    private function _getToken($url)
    {
        $token = ["url" => $url];
        return \Firebase\JWT\JWT::encode($token, \Traffic\LpToken\Service\LpTokenService::generateUserKey($this->getRawClick()->getUserAgent()));
    }
    private function _getGatewayBaseUrl()
    {
        $uri = $this->getServerRequest()->getUri();
        $url = $uri->getScheme() . "://";
        $url .= \Traffic\Service\UrlService::instance()->stripHostWww($uri);
        $url .= "/gateway.php";
        return $url;
    }
}

?>