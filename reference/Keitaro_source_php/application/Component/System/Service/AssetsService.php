<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\Service;

class AssetsService extends \Traffic\Service\AbstractService
{
    const DEFAULT_WEBPACK_SERVER_PORT = "8090";
    const PRODUCTION_ASSETS_PATH = "assets";
    public function url($isDevelopment, \Traffic\Request\ServerRequest $serverRequest)
    {
        if (!$isDevelopment) {
            return PRODUCTION_ASSETS_PATH;
        }
        $port = \Traffic\Service\ConfigService::instance()->get("dev", "webpack_server_port", DEFAULT_WEBPACK_SERVER_PORT);
        $host = \Traffic\Service\ConfigService::instance()->get("dev", "webpack_server_host");
        $uri = $serverRequest->getUri();
        if (empty($host)) {
            $host = $uri->getHost();
        }
        $colonPos = strpos($host, ":");
        if ($colonPos !== false) {
            $host = substr($host, 0, $colonPos);
        }
        return "//" . $host . ":" . $port;
    }
}

?>