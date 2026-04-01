<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Dispatcher;

class UpdateTokensDispatcher implements \Core\Dispatcher\DispatcherInterface
{
    public function dispatch(\Traffic\Request\ServerRequest $request)
    {
        $response = \Traffic\Response\Response::build(["disable_cache" => true]);
        if (!$request->hasParam("sub_id")) {
            $response = \Traffic\Response\ResponseFactory::build(["status" => 400, "body" => "[UpdateTokens] SubId is empty in : " . json_encode($request->getAllParams())]);
            return $response;
        }
        \Traffic\Command\DelayedCommand\UpdateClickCommand::updateTokens($request->getParam("sub_id"), $request->getAllParams());
        return $response;
    }
}

?>