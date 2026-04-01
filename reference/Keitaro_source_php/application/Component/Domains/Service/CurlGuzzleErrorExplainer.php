<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Domains\Service;

class CurlGuzzleErrorExplainer extends \Traffic\Service\AbstractService
{
    public function getHumanError(\GuzzleHttp\Exception\TransferException $exception)
    {
        if (strpos($exception->getMessage(), "cURL error") === false) {
            return $exception->getMessage();
        }
        return $this->_getErrorText($exception);
    }
    private function _getErrorText(\GuzzleHttp\Exception\TransferException $exception)
    {
        preg_match("/cURL error (?<errorCode>\\d{1,2}):.*/", $exception->getMessage(), $matches);
        $errorCode = isset($matches["errorCode"]) ? $matches["errorCode"] : NULL;
        return !is_null($errorCode) ? curl_strerror($errorCode) : "Unknown cURL error";
    }
}

?>