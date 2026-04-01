<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class UpdateParamsFromLandingStage implements StageInterface
{
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $allowedParams = ["creative_id"];
        for ($num = 1; $num <= \Traffic\Model\Click::getSubIdCount(); $num++) {
            $allowedParams[] = "sub_id_" . $num;
        }
        $request = $payload->getServerRequest();
        $rawClick = $payload->getRawClick();
        foreach ($allowedParams as $paramName) {
            if ($request->hasParam($paramName)) {
                $rawClick->set($paramName, \Traffic\Tools\Tools::utf8ize($request->getParam($paramName)));
            }
        }
        $payload->setRawClick($rawClick);
        return $payload;
    }
}

?>