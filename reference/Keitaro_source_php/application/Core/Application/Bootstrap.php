<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Application;

class Bootstrap
{
    const REMEMBER_ME_INTERVAL = 1209600;
    const SAPI_NAME_CLI = "cli";
    public static function initAdminContext()
    {
        self::initTimeZone();
        self::initApplication();
        self::initConfig();
        self::initLogger();
        self::checkDependencies();
        self::initSession();
        self::initSettingsCache();
        self::initSettingsFromDb();
        self::initCommon();
        self::initValidators();
    }
    public static function initTestContext()
    {
        self::initTimeZone();
        self::initApplication();
        self::initConfig();
        self::initLogger();
        self::checkDependencies();
        self::initSettingsCache();
        self::initSession();
        self::initCommon();
        self::initValidators();
    }
    public static function initClickContext()
    {
        self::initTimeZone();
        self::initApplication();
        self::initConfig();
        self::initLogger();
        self::initSettingsCache();
        self::initSettingsFromCache();
        self::initDbStatus();
        self::initCommon();
    }
    public static function initCliContext()
    {
        self::initTimeZone();
        self::initApplication();
        self::initConfig(true);
        self::initLogger();
        if (\Core\Db\Db::instance()->isEnabled()) {
            self::initSettingsCache();
            self::initSettingsFromDb();
        }
        self::initCommon();
        self::checkDependencies();
        self::initValidators();
    }
    public static function initCommon()
    {
        self::initErrorHandler();
        self::initBackCompatibility();
        self::initLicense();
        self::initHttpService();
        self::initCacheService();
        self::initFsService();
        self::initCacheService();
        self::initEntityCache();
        self::initDelayedCommands();
        self::initLpTokenService();
        self::initHitLimitService();
        self::initLocalFileService();
    }
    public static function initApplication()
    {
        if (file_exists(ROOT . "/DEVELOP")) {
            Application::instance()->setEnv(Application::DEVELOPMENT);
        }
        if (defined("ENV")) {
            Application::instance()->setEnv(ENV);
        }
        if (getenv(\Component\System\Service\StatusService::RR_ENV_PARAM)) {
            Application::instance()->setRunningRoadRunner();
        }
        if (php_sapi_name() === SAPI_NAME_CLI) {
            Application::instance()->setCli(true);
        }
    }
    public static function initConfig($ignoreEmptyConfig = false)
    {
        $path = \Traffic\Service\ConfigService::instance()->findConfigPath();
        if (file_exists($path) || !$ignoreEmptyConfig) {
            \Traffic\Service\ConfigService::instance()->init($path);
        }
        if (!defined("SALT")) {
            define("SALT", \Traffic\Service\ConfigService::instance()->get("system", "salt"));
        }
        if (!defined("TDS_VERSION")) {
            define("TDS_VERSION", include ROOT . "/version.php");
        }
        if (\Traffic\Service\ConfigService::instance()->getDebugValue()) {
            Application::instance()->setDebug(true);
        }
        $revision = filemtime(ROOT . "/version.php");
        Application::instance()->setRevision($revision);
        if (!ini_get("session.save_handler") == "files" && !@is_writable(@ini_get("session.save_path"))) {
            ini_set("session.save_path", ROOT . "/var/sessions");
        }
        if (function_exists("mb_internal_encoding")) {
            mb_internal_encoding("utf-8");
        }
    }
    public static function initTimeZone()
    {
        date_default_timezone_set("UTC");
    }
    public static function initErrorHandler()
    {
        if (Application::instance()->isDebug() || Application::instance()->isTesting()) {
            @ini_set("display_errors", 1);
        } else {
            @ini_set("display_errors", 0);
        }
    }
    public static function initLogger()
    {
        if (!Application::instance()->isTesting()) {
            \Traffic\Logging\Service\LoggerService::instance()->setErrorHandlers();
        }
        if (Application::instance()->isProduction()) {
            \Traffic\Logging\Service\LoggerService::instance()->enableBuffer();
        }
    }
    public static function initSettingsCache()
    {
        if (Application::instance()->isTesting()) {
            $path = \Traffic\Repository\CachedSettingsRepository::defaultTestingCachePath();
        } else {
            $path = \Traffic\Repository\CachedSettingsRepository::defaultCachePath();
        }
        $defaultOptions = [\Traffic\Cache\Cache::CACHE_DIRECTORY => $path];
        $settingsCache = \Traffic\Cache\CacheFactory::build(\Traffic\Cache\CacheService::SETTINGS, \Traffic\Cache\Cache::STORAGE_FILES, $defaultOptions);
        \Traffic\Cache\CacheService::instance()->setCache(\Traffic\Cache\CacheService::SETTINGS, $settingsCache);
    }
    public static function initSettingsFromDb()
    {
        self::initSettingsCache();
        $settings = \Traffic\Settings\Repository\SettingsRepository::instance()->allAsHash();
        \Traffic\Repository\CachedSettingsRepository::instance()->setCachedSettings($settings);
        \Traffic\Service\SettingsService::instance()->checkDefaults();
    }
    public static function initSettingsFromCache()
    {
        $settings = \Traffic\Repository\CachedSettingsRepository::instance()->loadFromCache();
        \Traffic\Repository\CachedSettingsRepository::instance()->setCachedSettings($settings);
    }
    public static function enableDebugErrors()
    {
        ini_set("display_errors", "on");
        ini_set("log_errors_max_len", 0);
        error_reporting(E_ALL);
    }
    public static function disableDebugErrors()
    {
        ini_set("display_errors", "off");
    }
    public static function initValidators()
    {
        \Valitron\Validator::addRule("uniqueness", function ($field, $value, $options, $data) {
            $definition = $options[0];
            if (!$definition instanceof \Core\Entity\Definition\EntityDefinition) {
                throw new \Exception("first option for 'uniqueness' validation must be EntityDefinition,\n                    got " . $definition);
            }
            $where = [];
            if (strpos($field, ",") === false) {
                $fieldNames = explode(",", $field);
                foreach ($fieldNames as $fieldName) {
                    $fieldName = trim($fieldName);
                    $where[] = "`" . $fieldName . "` = " . \Core\Db\Db::quote(isset($data[$fieldName]) ? $data[$fieldName] : NULL);
                }
            } else {
                $where[] = "`" . $field . "` = " . \Core\Db\Db::quote($value);
            }
            if (isset($data["id"])) {
                $where[] = "id <> " . \Core\Db\Db::quote($data["id"]);
            }
            if (isset($options[1])) {
                $condition = $options[1];
                if (preg_match_all("/\\{(.+?)\\}/si", $condition, $matches)) {
                    foreach ($matches[0] as $i => $match) {
                        $condition = str_replace($matches[0][$i], \Core\Db\Db::quote($data[$matches[1][$i]]), $condition);
                    }
                }
                $where[] = $condition;
            }
            return !$definition->repository()->exists(implode(" AND ", $where));
        });
        \Valitron\Validator::lang(\Core\Locale\LocaleService::instance()->getLanguage());
        \Valitron\Validator::langDir(ROOT . "/application/Core/Validator/lang/");
    }
    public static function checkDependencies()
    {
        $check = ["iconv", "json", "zip", "hash", "mbstring", "filter"];
        foreach ($check as $ext) {
            if (!extension_loaded($ext)) {
                throw new Exception\Error("Extension '" . $ext . "' not installed.");
            }
        }
    }
    public static function initSession()
    {
        if (!headers_sent()) {
            ini_set("session.name", "keitaro");
            ini_set("session.cookie_path", "/");
            ini_set("session.use_cookies", 1);
            ini_set("session.use_only_cookies", 1);
            ini_set("session.cookie_lifetime", REMEMBER_ME_INTERVAL);
            ini_set("session.cache_expire", REMEMBER_ME_INTERVAL);
            ini_set("session.gc_maxlifetime", REMEMBER_ME_INTERVAL + 600);
        }
    }
    public static function initBackCompatibility()
    {
        if (\Traffic\Service\ConfigService::instance()->get("system", "compatibility_v8", true)) {
            \Traffic\BackCompatibility\BackCompatibility::init();
        }
    }
    public static function initHttpService()
    {
        if (Application::instance()->isTesting()) {
            \Traffic\Http\Service\HttpService::instance()->setClient(new \Traffic\Http\HttpMockClient());
        } else {
            \Traffic\Http\Service\HttpService::instance()->buildDefaultClient();
        }
    }
    public static function initCacheService($rootFolder = false)
    {
        $cacheStorageName = \Traffic\Repository\CachedSettingsRepository::instance()->get("cache_storage", \Traffic\Cache\Cache::STORAGE_FILES);
        $defaultOptions = [\Traffic\Cache\Cache::CACHE_DIRECTORY => $rootFolder . "/cache"];
        $commonCache = \Traffic\Cache\CacheFactory::build(\Traffic\Cache\CacheFactory::COMMON, $cacheStorageName, $defaultOptions);
        $dataCache = \Traffic\Cache\CacheFactory::build(\Traffic\Cache\CacheService::CACHED_DATA, $cacheStorageName, $defaultOptions);
        $deviceDetectorCache = \Traffic\Cache\CacheFactory::build(\Traffic\Cache\CacheService::DEVICE_DETECTOR, $cacheStorageName, $defaultOptions);
        $lpOfferCache = \Traffic\Cache\CacheFactory::build(\Traffic\Cache\CacheService::LP_OFFER, $cacheStorageName, [\Traffic\Cache\Cache::CACHE_DIRECTORY => $rootFolder . "/cache_landing"]);
        \Traffic\Cache\CacheService::instance()->setCaches([\Traffic\Cache\CacheService::COMMON => $commonCache, \Traffic\Cache\CacheService::LP_OFFER => $lpOfferCache, \Traffic\Cache\CacheService::CACHED_DATA => $dataCache, \Traffic\Cache\CacheService::DEVICE_DETECTOR => $deviceDetectorCache]);
    }
    public static function initEntityCache()
    {
        $cacheStorageName = \Traffic\Repository\CachedSettingsRepository::instance()->get("cache_storage", \Traffic\Cache\Cache::STORAGE_FILES);
        $compressionEnabled = Application::instance()->isCompressionEnabled();
        $storage = \Traffic\CachedData\Storage\Factory::build($cacheStorageName, $compressionEnabled);
        \Core\EntityEventManager\Service\EntityEventService::instance()->setStorage($storage);
        \Traffic\CachedData\Repository\CachedDataRepository::instance()->setStorage($storage);
    }
    public static function initDbStatus()
    {
        \Core\Db\Db::instance()->disable();
    }
    public static function initDelayedCommands()
    {
        $name = \Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::DRAFT_DATA_STORAGE, \Component\DelayedCommands\Repository\DelayedCommandsStorageRepository::FILE);
        if (!\Component\DelayedCommands\Repository\DelayedCommandsStorageRepository::instance()->exists($name)) {
            $name = \Component\DelayedCommands\Repository\DelayedCommandsStorageRepository::FILE;
        }
        $storage = \Component\DelayedCommands\Repository\DelayedCommandsStorageRepository::instance()->getStorage($name);
        if (Application::instance()->isCompressionEnabled()) {
            $storage->enableCompression();
        }
        \Traffic\CommandQueue\Service\DelayedCommandService::instance()->setStorage($storage);
    }
    public static function initHitLimitService()
    {
        $storage = \Traffic\HitLimit\Storage\Factory::build(\Traffic\Redis\Service\RedisStorageService::instance()->draftStorageUsesRedis());
        \Traffic\HitLimit\Service\HitLimitService::instance()->setStorage($storage);
    }
    public static function initLpTokenService()
    {
        $compressionEnabled = Application::instance()->isCompressionEnabled();
        $storage = \Traffic\LpToken\Storage\Factory::build($compressionEnabled);
        \Traffic\LpToken\Service\LpTokenService::instance()->setStorage($storage);
    }
    public static function initLicense()
    {
        $ip = \Core\Security\IpResolverService::instance()->findLicenseIp();
        LicenseService::instance()->setLicenseIp($ip);
        EssentialService::instance()->loadFeatures();
    }
    public static function shutdownCommon()
    {
    }
    public static function initFsService()
    {
        \Core\FileSystem\Service\FileSystemService::instance()->setAdapter(\Core\FileSystem\Service\FileSystemService::defaultAdapter());
    }
    public static function initLocalFileService()
    {
        $landerPath = join("/", [ROOT, \Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::LP_DIR, "lander")]);
        \Component\Landings\LocalFile\LocalFileService::instance()->setPath($landerPath);
    }
}

?>