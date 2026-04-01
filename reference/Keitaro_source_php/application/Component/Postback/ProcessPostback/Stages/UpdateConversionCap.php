<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Postback\ProcessPostback\Stages;

class UpdateConversionCapStage implements \Component\Postback\ProcessPostback\StageInterface
{
    public function process(\Component\Postback\ProcessPostback\Payload $payload)
    {
        $offer = $payload->getOffer();
        if (empty($offer)) {
            return $payload;
        }
        if (!$offer->isConversionCapEnabled()) {
            return $payload;
        }
        $conversion = $payload->getNewConversion();
        if (empty($conversion)) {
            return $payload;
        }
        \Core\Logging\Service\PostbackLoggerService::instance()->log("Update conversion cap for offer #" . $offer->getId());
        \Component\Conversions\ConversionCapacity\Service\ConversionCapacityService::instance()->apply($offer, $conversion);
        return $payload;
    }
}

?>