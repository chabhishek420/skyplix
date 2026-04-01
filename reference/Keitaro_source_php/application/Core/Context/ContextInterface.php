<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Context;

final class ContextInterface
{
    public abstract function bootstrap();
    public abstract function modifyRequest(\Traffic\Request\ServerRequest $serverRequest);
    public abstract function dispatcher(\Traffic\Request\ServerRequest $serverRequest);
    public abstract function shutdown();
    public abstract function handleException(\Exception $e, \Traffic\Request\ServerRequest $serverRequest);
}

?>