<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Model;

class Setting extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_tableName = "settings";
    protected static $_primaryKey = "key";
    const IPDB = "ipdb";
    const CHECK_BOT_UA = "check_bot_ua";
    const GEODB = "geodb";
    const DRAFT_DATA_STORAGE = "draft_data_storage";
    const IMKLO_API_URL = "imklo_api_url";
    const HIDE_CLICK_KEY = "hide_click_key";
    const LP_DIR = "lp_dir";
    const LP_ALLOW_PHP = "lp_allow_php";
    const LP_PHP_TIMEOUT = "lp_php_timeout";
    const TRAFFIC_LOG_ENABLED = "traffic_log_enabled";
    const CACHE_STORAGE = "cache_storage";
    const AVOID_MYSQL = "avoid_mysql";
    const LP_OFFER_TOKEN_TTL = "lp_offer_token_ttl";
    const SECURE_TRANSPORT = "secure_transport";
    const EXTRA_ACTION = "extra_action";
    const EXTRA_CAMPAIGN = "extra_campaign";
    const EXTRA_ACTION_PARAM_REDIRECT = "redirect";
    const EXTRA_ACTION_PARAM_NOT_FOUND = "not_found";
    const EXTRA_ACTION_PARAM_CAMPAIGN = "campaign";
    const LP_OFFER_TOKEN_TTL_MAX = 43200;
    const LP_PHP_TIMEOUT_MAX = 20;
    const ADMIN_ALLOWED_DOMAINS = "admin_allowed_domains";
    public static function service()
    {
        return \Traffic\Service\SettingsService::instance();
    }
    public function getValue()
    {
        return $this->get("value");
    }
}

?>