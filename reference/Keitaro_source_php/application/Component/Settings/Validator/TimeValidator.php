<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Settings\Validator;

class TimeValidator extends \Core\Validator\AbstractValidator
{
    public function validate($params)
    {
        $time = @mktime($params["hour"], $params["minute"], $params["second"], $params["month"], $params["day"], $params["year"]);
        if ($time <= 0) {
            $this->_throwErrors(["time" => [\Core\Locale\LocaleService::instance()->t("settings.time.incorrect_format")]]);
        }
    }
}

?>