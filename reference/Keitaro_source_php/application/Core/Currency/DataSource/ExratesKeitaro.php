<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Currency\DataSource;

class ExratesKeitaro implements DataSourceInterface
{
    const EXCHANGER_URL = "https://exrates.keitaro.io/latest?base=%from%&symbols=%to%";
    public function convert($from, $to)
    {
        $url = str_replace("%from%", $from, EXCHANGER_URL);
        $url = str_replace("%to%", $to, $url);
        $headers = ["x-license-key" => \Core\Application\LicenseService::instance()->getKey()];
        try {
            $content = \Traffic\Http\Service\HttpService::instance()->get($url, $headers)->getBody();
            $data = json_decode($content, true);
            if (empty($data) || empty($data["rates"][$to])) {
                $error = json_last_error();
                throw new CurrencyRequestError("Failed to convert: " . $from . " to " . $to . ". Error: " . $error);
            }
            return $data["rates"][$to];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new CurrencyRequestError("Failed to request exchange service. Error code: " . $e->getCode() . ".");
        }
    }
}

?>