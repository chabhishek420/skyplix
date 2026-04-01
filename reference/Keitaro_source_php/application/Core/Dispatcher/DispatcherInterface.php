<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Dispatcher;

final class DispatcherInterface
{
    public abstract function dispatch(\Traffic\Request\ServerRequest $request);
}

?>