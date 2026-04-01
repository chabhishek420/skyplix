<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Domains\DTO;

class DomainCheckResultValueObject
{
    private $isActive = NULL;
    private $error = NULL;
    public function __construct($error)
    {
        $this->error = is_null($error) ? $error : $this->_getErrorMessage($error);
        $this->isActive = is_null($this->error);
    }
    public function isActive()
    {
        return $this->isActive;
    }
    public function getError()
    {
        return $this->error;
    }
    private function _getErrorMessage($error)
    {
        $errorMessage = "Unknown error";
        if (is_string($error)) {
            $errorMessage = $error;
        } else {
            if ($error instanceof \Exception) {
                $errorMessage = $this->_getErrorMessageFromException($error);
            }
        }
        return $errorMessage;
    }
    private function _getErrorMessageFromException(\Exception $e)
    {
        if ($e instanceof \GuzzleHttp\Exception\BadResponseException) {
            return $e->getResponse()->getStatusCode() . " " . $e->getResponse()->getReasonPhrase();
        }
        if ($e instanceof \GuzzleHttp\Exception\TransferException) {
            return \Component\Domains\Service\CurlGuzzleErrorExplainer::instance()->getHumanError($e);
        }
        return $e->getMessage();
    }
}

?>