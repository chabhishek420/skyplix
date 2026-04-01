<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Security;

class ServerFinderService extends \Traffic\Service\AbstractService
{
    const ENV_FILE = "/../.env";
    const SERVER_LICENSE_TYPE = "license";
    const SERVER_UPDATE_TYPE = "update";
    public function getTrustedIps()
    {
        return ["159.69.13.34", "116.203.2.5", "162.55.209.40", "162.55.187.193", "94.130.168.208", "94.130.175.223", "195.201.25.233", "78.47.63.169", "116.203.216.19", "78.47.70.26"];
    }
    public function shouldVerifySsl($server)
    {
        return strpos($server, "https") !== false;
    }
    public function findServer($serverType)
    {
        $servers = $this->getServerList($serverType);
        foreach ($servers as $server) {
            $uri = $server["uri"] . "/ping";
            try {
                $verifySsl = $this->shouldVerifySsl($server["uri"]);
                $response = \Traffic\Http\Service\HttpService::instance()->get($uri, [], [\GuzzleHttp\RequestOptions::VERIFY => $verifySsl, \GuzzleHttp\RequestOptions::CONNECT_TIMEOUT => \Core\Application\EssentialService::CONNECTION_TIMEOUT, \GuzzleHttp\RequestOptions::TIMEOUT => \Core\Application\EssentialService::CONNECTION_TIMEOUT]);
                if (stristr($response->getBody(), "pong")) {
                    return $server;
                }
            } catch (\GuzzleHttp\Exception\RequestException $exception) {
            }
        }
        return $this->getServerList($serverType)[0];
    }
    public function getServerList($serverType)
    {
        $servers = [SERVER_LICENSE_TYPE => [["uri" => "https://license.keitaro.io", "path" => "/v1/token/v8"], ["uri" => "https://reserve.license.keitaro.io", "path" => "/v1/token/v8"]], SERVER_UPDATE_TYPE => [["uri" => "https://keitaro.io"], ["uri" => "http://159.69.13.34"]]];
        if (\Core\Application\Application::instance()->isDevelopment()) {
            array_unshift($servers[SERVER_LICENSE_TYPE], ["uri" => $this->getLicenserServer(), "path" => "/v1/token/v8"]);
        }
        return $servers[$serverType];
    }
    public function getLicenserServer()
    {
        $env = \Jsefton\DotEnv\Parser::envToArray(ROOT . ENV_FILE);
        return "http://" . $env["LICENSER_HOST"] . ":" . $env["LICENSER_PORT"];
    }
}

?>