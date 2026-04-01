<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Repository;

class CachedSettingsRepository extends AbstractBaseRepository
{
    private $_cachedSettings = [];
    const CACHE_KEY = "settings";
    public static function defaultCachePath()
    {
        return ROOT . \Traffic\Cache\Cache::DEFAULT_CACHE_DIR;
    }
    public static function defaultTestingCachePath()
    {
        return TESTS_ROOT . \Traffic\Cache\Cache::DEFAULT_CACHE_DIR;
    }
    public function getCache()
    {
        return \Traffic\Cache\CacheService::instance()->settingsCache();
    }
    public function setCachedSettings($settings)
    {
        $this->_cachedSettings = $settings;
    }
    public function cleanCache()
    {
        $this->_cachedSettings = NULL;
        $cache = $this->getCache();
        if (!empty($cache)) {
            $cache->delete(CACHE_KEY);
        }
    }
    public function getCachedSettings()
    {
        return $this->_cachedSettings;
    }
    public function has($key)
    {
        return array_key_exists($key, $this->_cachedSettings);
    }
    public function get($key, $default = NULL)
    {
        if ($this->has($key)) {
            return $this->_cachedSettings[$key];
        }
        if (isset($default)) {
            return $default;
        }
        return NULL;
    }
    public function loadFromCache()
    {
        try {
            $settings = $this->getCache()->get(CACHE_KEY);
            return $settings;
        } catch (\Traffic\Cache\NoCache $e) {
            \Traffic\Logging\Service\LoggerService::instance()->warning("Settings cache is empty! It must be reloaded (Maintenance > Status).");
            return [];
        }
    }
    public function warmup()
    {
        $settings = \Traffic\Settings\Repository\SettingsRepository::instance()->allAsHash();
        $this->setCachedSettings($settings);
        \Traffic\Cache\CacheService::instance()->settingsCache()->set(CACHE_KEY, $settings);
    }
}

?>