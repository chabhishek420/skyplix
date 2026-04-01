<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\AppsFlyer;

class AppsFlyerLog
{
    public static function success(AppsFlyerIntegrationSettings $integration)
    {
        $data = ["status" => "Success", "last_update" => time()];
        \Component\ThirdPartyIntegration\Service\ThirdPartyIntegrationService::instance()->updateValues($integration->getId(), $data);
    }
    public static function error(AppsFlyerIntegrationSettings $integration, $last_error, $last_raw_error = NULL)
    {
        $data = ["status" => "Error", "last_error" => $last_error, "last_raw_error" => $last_raw_error, "last_update" => time()];
        \Component\ThirdPartyIntegration\Service\ThirdPartyIntegrationService::instance()->updateValues($integration->getId(), $data);
    }
}

?>