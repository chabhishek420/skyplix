<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Home\Service;

class JsConfigService extends \Traffic\Service\AbstractService
{
    public function get(\Traffic\Request\ServerRequest $serverRequest)
    {
        $user = \Component\Users\Service\CurrentUserService::instance()->get();
        $result = ["version" => TDS_VERSION, "debug" => \Core\Application\Application::instance()->isDevelopment(), "demo" => \Traffic\Service\ConfigService::instance()->isDemo(), "user" => \Core\Json\SerializerFactory::serialize($user, new \Component\Users\Serializer\UserSerializer()), "server_ip" => \Core\Application\LicenseService::instance()->getLicenseIp(), "max_file_size" => \Core\Application\Application::instance()->getMaxFileSize(), "lp_dir" => \Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::LP_DIR), "lp_allow_php" => \Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::LP_ALLOW_PHP), "lp_php_timeout" => \Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::LP_PHP_TIMEOUT), "mandatory_resource" => \Component\Users\Repository\AclResourceRepository::instance()->getMandatory(), "edition" => \Core\Application\FeatureService::instance()->getEdition(), "license_expires_at" => \Core\Application\FeatureService::instance()->getLicenseExpireTime()->format(\DateTimeInterface::ISO8601), "av_enabled" => \Traffic\Repository\CachedSettingsRepository::instance()->get("avscan_key") || \Traffic\Repository\CachedSettingsRepository::instance()->get("vcm_key"), "av_scan_page_allowed" => \Traffic\Repository\CachedSettingsRepository::instance()->get("vcm_key"), "base_path" => \Traffic\Service\UrlService::instance()->getBasePath($serverRequest->getUri(), 2), "postback_secret" => \Component\AffiliateNetworks\Repository\NetworkTemplatesRepository::instance()->getSecret(), "api_key" => \Traffic\Repository\CachedSettingsRepository::instance()->get("api_key"), "isp_db" => \Traffic\GeoDb\Repository\GeoDbRepository::instance()->isDataTypeAvailable(\Traffic\GeoDb\IpInfoType::ISP), "connection_type_db" => \Traffic\GeoDb\Repository\GeoDbRepository::instance()->isDataTypeAvailable(\Traffic\GeoDb\IpInfoType::CONNECTION_TYPE), "currency" => \Core\Currency\Service\CurrencyService::instance()->getCurrent(), "currency_symbols" => \Core\Currency\Repository\CurrenciesRepository::instance()->getData(), "trial_welcome_shown" => \Traffic\Repository\CachedSettingsRepository::instance()->get("trial_welcome_shown"), "show_extra_param" => \Traffic\Repository\CachedSettingsRepository::instance()->get("show_extra_param"), "is_sidebar_enabled" => \Traffic\Repository\CachedSettingsRepository::instance()->get("is_sidebar_enabled"), "users_limit" => \Core\Application\FeatureService::instance()->getUsersLimit(), "has_sub_id_15" => \Traffic\Repository\ParameterRepository::instance()->hasSubId15(), "campaign_autosave" => \Traffic\Repository\CachedSettingsRepository::instance()->get("campaign_autosave"), "traffic_log_enabled" => \Traffic\Repository\CachedSettingsRepository::instance()->get("traffic_log_enabled"), "resource_names" => \Component\Users\Repository\AclResourceRepository::instance()->getAll(), "redis_uri_in_settings" => !\Traffic\Service\ConfigService::instance()->has("redis", "uri"), "cookies_enabled" => \Traffic\Repository\CachedSettingsRepository::instance()->get("cookies_enabled", 1), "language" => \Core\Locale\LocaleService::instance()->getLanguage()];
        if (\Core\Application\FeatureService::instance()->hasBrandingFeature() && \Core\Db\DataRepository::instance()->tableExists(\Component\Branding\Model\Branding::definition())) {
            $logo = \Component\Branding\Repository\BrandingRepository::instance()->getLogo();
            if (!empty($logo)) {
                $result["custom_logo"] = $logo;
            }
        }
        return $result;
    }
}

?>