<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\Facebook;

class FacebookLog
{
    public static function success(FbIntegrationSettings $integration)
    {
        $data = ["status" => "Success", "last_update" => time()];
        \Component\ThirdPartyIntegration\Service\ThirdPartyIntegrationService::instance()->updateValues($integration->getId(), $data);
    }
    public static function error(FbIntegrationSettings $integration, $last_error, $last_raw_error = NULL)
    {
        $data = ["status" => "Error", "last_error" => $last_error, "last_raw_error" => $last_raw_error];
        \Component\ThirdPartyIntegration\Service\ThirdPartyIntegrationService::instance()->updateValues($integration->getId(), $data);
    }
}

?>