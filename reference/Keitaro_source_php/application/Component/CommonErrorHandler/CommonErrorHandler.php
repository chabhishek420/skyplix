<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\CommonErrorHandler;

class CommonErrorHandler
{
    const GENERAL_ERROR_MESSAGE = "An error occurred. Please check Maintenance > Log";
    const TYPE_GREETING = "greeting";
    const TYPE_EXPIRES = "expires";
    public static function handleAny(\Exception $e)
    {
        if ($e instanceof \Core\Application\Exception\LicenseError) {
            $message = \Traffic\Tools\Tools::demodulize(get_class($e)) . ": " . $e->getMessage() . " \n" . $e->getTraceAsString();
        } else {
            $message = \Traffic\Tools\Tools::demodulize(get_class($e)) . ": " . $e->getMessage();
        }
        \Traffic\Logging\Service\LoggerService::instance()->error($message);
        if (!\Core\Application\Application::instance()->isDebug()) {
            $message = GENERAL_ERROR_MESSAGE;
        }
        return \Traffic\Response\Response::buildHtml(["status" => 500, "body" => $message]);
    }
    public static function handleLicenseError(\Exception $e, \Traffic\Request\ServerRequest $serverRequest)
    {
        \Core\Locale\LocaleService::instance()->getLanguage() ? exit : \Core\Locale\LocaleService::DEFAULT_LANGUAGE;
    }
}

?>