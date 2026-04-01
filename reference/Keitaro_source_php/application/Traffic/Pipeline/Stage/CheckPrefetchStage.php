<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class CheckPrefetchStage implements StageInterface
{
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $request = $payload->getServerRequest();
        if (empty($request)) {
            throw new StageException("Empty request");
        }
        if (!\Traffic\Repository\CachedSettingsRepository::instance()->get("ingore_prefetch")) {
            return $payload;
        }
        if ($this->_isPrefetchDetected($request) || $request->getParam("version") && $request->getParam("prefetch")) {
            $logEntry->add("Ignored because prefetch is detected");
            $payload->setResponse(\Traffic\Response\ResponseFactory::build(["body" => "Prefetch not allowed", "status" => 403]));
            $payload->abort();
        }
        return $payload;
    }
    private function _isPrefetchDetected(\Traffic\Request\ServerRequest $request)
    {
        $checkServerParams = ["X-PURPOSE" => "preview", "X-MOZ" => "prefetch", "X-FB-HTTP-ENGINE" => "Liger"];
        foreach ($checkServerParams as $name => $value) {
            if ($request->getHeaderLine($name) == $value) {
                return true;
            }
        }
        return false;
    }
}

?>