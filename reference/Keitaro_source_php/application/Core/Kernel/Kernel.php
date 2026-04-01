<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Kernel;

class Kernel
{
    public static function run(\Traffic\Request\ServerRequest $serverRequest, \Core\Context\ContextInterface $context)
    {
        $kernel = new Kernel();
        return $kernel->runApplication($serverRequest, $context);
    }
    public function runApplication(\Traffic\Request\ServerRequest $serverRequest, \Core\Context\ContextInterface $context)
    {
        try {
            if (empty($serverRequest)) {
                throw new \Exception("serverRequest is not set");
            }
            if (empty($context)) {
                throw new \Exception("Context must be provided");
            }
            $context->bootstrap();
            $this->_updateLoggerContext($context);
            $serverRequest = $context->modifyRequest($serverRequest);
            \Traffic\Request\ServerRequestFactory::extractSuperGlobals($serverRequest);
            $dispatcher = $context->dispatcher($serverRequest);
            if (!$dispatcher) {
                throw new \Exception("Context " . get_class($context) . " must return a Dispatcher");
            }
            $response = $dispatcher->dispatch($serverRequest);
            if (empty($response)) {
                throw new \Exception(get_class($dispatcher) . "#dispatch must return a Response");
            }
            $context->shutdown();
            $this->_closeDbConnection();
        } catch (\Exception $exception) {
            $response = $context->handleException($exception, $serverRequest);
            return $response;
        }
    }
    private function _updateLoggerContext(\Core\Context\ContextInterface $context)
    {
        $contextName = \Traffic\Tools\Tools::demodulize(strtolower(str_replace("Context", "", get_class($context))));
        \Traffic\Logging\Service\LoggerService::instance()->setContextName($contextName);
    }
    private function _closeDbConnection()
    {
        \Core\Db\Db::instance()->disconnect();
    }
}

?>