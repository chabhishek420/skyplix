<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Dispatcher;

class ClickDispatcher implements \Core\Dispatcher\DispatcherInterface
{
    public function dispatch(\Traffic\Request\ServerRequest $request)
    {
        $response = \Traffic\Response\Response::buildHtml(["disable_cache" => true]);
        if ($this->_isLicenseExpired()) {
            return $this->_getErrorResponse("License expired", 402);
        }
        $logEntry = \Traffic\Logging\Service\TrafficLoggerService::instance()->entry();
        try {
            $pipelinePayload = new \Traffic\Pipeline\Payload(["server_request" => $request, "response" => $response, "raw_click" => new \Traffic\RawClick(), "force_redirect_offer" => true]);
            $pipeline = new \Traffic\Pipeline\Pipeline();
            $pipeline->firstLevelStages();
            $pipelinePayload = $pipeline->start($pipelinePayload, $logEntry);
            $response = $pipelinePayload->getResponse();
            $logEntry->logRawClick($pipelinePayload->getRawClick(), $request);
        } catch (\Traffic\Pipeline\Stage\StageException $e) {
            $logEntry->add($e->getMessage());
            \Traffic\Logging\Service\LoggerService::instance()->error($e->getMessage());
            $response = $this->_getErrorResponse($e->getMessage());
            if (empty($response)) {
                \Traffic\Logging\Service\LoggerService::instance()->error("Empty response on " . (int) $request->getUri());
                return $this->_getErrorResponse($e->getMessage());
            }
            if (\Traffic\Service\ConfigService::instance()->isDemo()) {
                return $this->_buildDemoResponse($response);
            }
            return $response;
        }
    }
    private function _getErrorResponse($message, $status = 501)
    {
        if (!\Core\Application\Application::instance()->isDebug()) {
            $message = "Sorry. Some internal problems. Please read System Log.";
        }
        return \Traffic\Response\Response::buildHtml(["body" => $message, "status" => $status]);
    }
    private function _buildDemoResponse(\Traffic\Response\Response $response)
    {
        $newBody = "<b>This is demo version and all action and landings are blocked</b><br><br>";
        $newBody .= "In general version output would be:<br>";
        $newBody .= "Headers: <br>";
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $newBody .= "<div style='margin-left:20px'>" . $name . ":" . $value . "</div>";
            }
        }
        $newBody .= "Body: <br>";
        $newBody .= htmlentities((int) $response->getBody());
        return \Traffic\Response\Response::buildHtml(["body" => $newBody]);
    }
    private function _isLicenseExpired()
    {
        if (!\Core\Application\TsService::instance()->shouldCheckTsFile()) {
            return false;
        }
        if (!\Core\Application\TsService::instance()->isTimestampActive()) {
            $msg = "An error occurred: the license expired";
            if (!\Core\Application\TsService::instance()->isFileExists()) {
                $msg .= " [no tfile]";
            }
            \Traffic\Logging\Service\LoggerService::instance()->error($msg);
            return true;
        }
        return false;
    }
}

?>