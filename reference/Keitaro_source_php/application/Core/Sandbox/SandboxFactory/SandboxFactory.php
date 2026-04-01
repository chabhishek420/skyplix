<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Sandbox\SandboxFactory;

class SandboxFactory
{
    public static function create($specificEngine, \Core\Sandbox\CgiExecutor\CgiExecutorInterface $cgiExecutor, \Core\Sandbox\CgiExecutor\CgiExecutorInterface $fcgiExecutor)
    {
        if ($specificEngine === \Core\Sandbox\Sandbox::FCGI_ENGINE || empty($specificEngine) && $fcgiExecutor->isAvailable()) {
            \Traffic\Logging\Service\LoggerService::instance()->debug("Using sandbox with FCGI");
            return new \Core\Sandbox\Sandbox($fcgiExecutor);
        }
        if ($specificEngine === \Core\Sandbox\Sandbox::CGI_ENGINE || empty($specificEngine) && $cgiExecutor->isAvailable()) {
            \Traffic\Logging\Service\LoggerService::instance()->debug("Using sandbox with CGI");
            return new \Core\Sandbox\Sandbox($cgiExecutor);
        }
        \Traffic\Logging\Service\LoggerService::instance()->debug("Using UnsafeSandbox");
        return new \Core\Sandbox\UnsafeSandbox\UnsafeSandbox();
    }
}

?>