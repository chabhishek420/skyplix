<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class ExecuteActionStage implements StageInterface
{
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $stream = $payload->getStream();
        $rawClick = $payload->getRawClick();
        $actionType = $payload->getActionType();
        if (empty($rawClick)) {
            throw new StageException("Empty rawClick");
        }
        if (empty($actionType)) {
            $msg = "Empty actionType in campaign #";
            if ($payload->getCampaign()) {
                $msg .= $payload->getCampaign()->getId();
            }
            $logEntry->add($msg);
            return $payload;
        }
        if (!$payload->getResponse()) {
            throw new StageException("Empty response in payload");
        }
        $logEntry->add("Executing action \"" . $actionType . "\"");
        try {
            $action = \Traffic\Actions\Repository\StreamActionRepository::instance()->getNewActionInstance($actionType);
            if (empty($action)) {
                throw new StageException("Incorrect type \"" . $actionType . "\" in stream #" . $stream->getId());
            }
            if (\Traffic\BackCompatibility\BackCompatibility::isLegacyAction($action)) {
                $action->setResponse($payload->getResponse());
                $action->setPipelinePayload($payload);
                $response = \Traffic\BackCompatibility\BackCompatibility::executeLegacyAction($action, $payload);
                $payload->setResponse($response);
            } else {
                if (!$action instanceof \Traffic\Actions\AbstractAction) {
                    throw new StageException("Redirect \"" . get_class($action) . "\" must be instance of AbstractAction");
                }
                $action->setPipelinePayload($payload);
                try {
                    $payload = $action->run();
                    if (get_class($action) === "Traffic\\Actions\\Predefined\\Remote") {
                        $logEntry->add("Remote redirect URL: " . $payload->getRawClick()->getDestination());
                    }
                } catch (\DomainException $e) {
                    throw new StageException($e->getMessage());
                }
            }
            return $payload;
        } catch (\Traffic\Actions\ActionError $e) {
            throw new StageException($e->getMessage());
        }
    }
}

?>