<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Admin\Controller\Helper;

interface ControllerHelper
{
    protected function throwDenyBecauseDemo();
    protected function throwDenyBecauseTrial();
    protected function throwDeny($message);
    protected function getControllerName();
    protected function throwNotFound($message);
    protected function throwError($message);
    public function throwPaymentRequired();
    public function isTrial();
    public function t($key, $params);
}

?>