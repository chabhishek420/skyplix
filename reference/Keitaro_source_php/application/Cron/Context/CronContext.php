<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Cron\Context;

class CronContext implements \Core\Context\ContextInterface
{
    const LICENSE_ERROR_TEXT = "A license error occurred. Please check the system log.";
    const GENERAL_ERROR_TEXT = "An error occurred. Please check the system log.";
    public function bootstrap()
    {
        if (\Core\Application\Application::instance()->isCli() || \Core\Application\Application::instance()->isDebug()) {
            \Core\Application\Bootstrap::enableDebugErrors();
        } else {
            \Core\Application\Bootstrap::disableDebugErrors();
        }
        \Core\Application\Bootstrap::initCliContext();
    }
    public function modifyRequest(\Traffic\Request\ServerRequest $serverRequest)
    {
        return $serverRequest;
    }
    public function dispatcher(\Traffic\Request\ServerRequest $serverRequest)
    {
        return new \Cron\Dispatcher\CronDispatcher();
    }
    public function shutdown()
    {
        \Core\EntityEventManager\Service\EntityEventService::instance()->emitEvents();
    }
    public function handleException(\Exception $e, \Traffic\Request\ServerRequest $serverRequest)
    {
        $fullMessage = $e->getMessage() . ": \n" . $e->getTraceAsString();
        if ($e instanceof \Core\Application\Exception\LicenseError) {
            \Traffic\Logging\Service\LoggerService::instance()->error($fullMessage);
            $response = \Traffic\Response\ResponseFactory::build(["status" => \Traffic\Response\StatusCode::INTERNAL_SERVER_ERROR, "body" => $fullMessage]);
            if (!\Core\Application\Application::instance()->isCli()) {
                $response = $response->withBody(\Traffic\Response\ResponseFactory::safeBody(GENERAL_ERROR_TEXT));
            }
        } else {
            $response = \Traffic\Response\ResponseFactory::build(["status" => \Traffic\Response\StatusCode::PAYMENT_REQUIRED, "body" => \Traffic\Response\ResponseFactory::safeBody(LICENSE_ERROR_TEXT)]);
        }
        return $response;
    }
}

?>