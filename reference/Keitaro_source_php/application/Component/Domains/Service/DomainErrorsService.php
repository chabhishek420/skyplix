<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Domains\Service;

class DomainErrorsService
{
    public $solutions = ["one_a_record" => ["Timeout was reached", "Domain returned empty response", "Domain returned unexpected response", "Couldn't connect to server", "Couldn't resolve host name", "Couldn't resolve proxy name"], "make_upgrade" => ["Peer certificate cannot be authenticated with given CA certificates", "Problem with the local SSL certificate", "Couldn't use specified SSL cipher"]];
    private $_localeService = NULL;
    public function __construct()
    {
        $this->_localeService = \Core\Locale\LocaleService::instance();
    }
    public function getErrorSolution($getErrorSolution, $errorText)
    {
        foreach ($this->solutions as $translateKey => $errorMessages) {
            foreach ($errorMessages as $errorMessage) {
                if (mb_strpos($errorText, $errorMessage) !== false) {
                    return $this->_localeService->t("domains.errors_solutions." . $translateKey);
                }
            }
        }
        return $errorText;
    }
}

?>