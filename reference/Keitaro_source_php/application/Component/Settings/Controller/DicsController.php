<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Settings\Controller;

class DicsController extends \Admin\Controller\BaseController
{
    public function currenciesAction()
    {
        return \Core\Currency\Repository\CurrenciesRepository::instance()->getCurrencies();
    }
}

?>