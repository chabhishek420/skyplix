<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Service;

class SettingsService extends \Core\Entity\Service\EntityService
{
    private $_ignoreKeys = ["correction_time"];
    const FOLDER_FILTER = "/[^a-z^0-9^-\\_\\.]/si";
    public function definition()
    {
        return \Traffic\Model\Setting::definition();
    }
    public function updateValue($key, $value)
    {
        $settings = [$key => $value];
        $this->updateValues($settings);
    }
    public function updateValues($newSettings)
    {
        $oldSettings = \Traffic\Settings\Repository\SettingsRepository::instance()->allAsHash();
        $newSettings = $this->_validateSettings($newSettings);
        $this->_renameLPDir($oldSettings, $newSettings);
        $this->_updateInDb($newSettings);
        $this->_warmupCache($newSettings, $oldSettings);
    }
    public function refreshCache()
    {
        \Traffic\Repository\CachedSettingsRepository::instance()->cleanCache();
        \Traffic\Repository\CachedSettingsRepository::instance()->warmup();
    }
    public function checkDefaults()
    {
        $apiKey = \Traffic\Repository\CachedSettingsRepository::instance()->get("api_key");
        if (empty($apiKey)) {
            $key = $this->_getNewApiKey();
            $this->updateValue("api_key", $key);
        }
    }
    public function resetDefaultActionOnCampaignDisable($campaignId)
    {
        $settingsRepository = \Traffic\Settings\Repository\SettingsRepository::instance();
        $extraActionSetting = $settingsRepository->findByKey(\Traffic\Model\Setting::EXTRA_ACTION);
        $extraCampaignSetting = $settingsRepository->findByKey(\Traffic\Model\Setting::EXTRA_CAMPAIGN);
        if ($extraActionSetting && $extraActionSetting->getValue() === \Traffic\Model\Setting::EXTRA_ACTION_PARAM_CAMPAIGN && $extraCampaignSetting && $extraCampaignSetting->getValue() == $campaignId) {
            $this->updateValues([\Traffic\Model\Setting::EXTRA_ACTION => \Traffic\Model\Setting::EXTRA_ACTION_PARAM_NOT_FOUND]);
            \Core\Db\DataService::instance()->delete(\Traffic\Model\Setting::definition(), $extraCampaignSetting);
        }
    }
    private function _validateSettings($_validateSettings, $newSettings)
    {
        $newSettings = $this->_capValue($newSettings, \Traffic\Model\Setting::LP_OFFER_TOKEN_TTL, \Traffic\Model\Setting::LP_OFFER_TOKEN_TTL_MAX);
        $newSettings = $this->_capValue($newSettings, \Traffic\Model\Setting::LP_PHP_TIMEOUT, \Traffic\Model\Setting::LP_PHP_TIMEOUT_MAX);
        $newSettings = $this->_validateNewLPDir($newSettings);
        array_filter($newSettings, function ($key) {
            return !in_array($key, $this->_ignoreKeys);
        }, ARRAY_FILTER_USE_KEY);
        return $newSettings;
    }
    private function _capValue($_capValue, $newSettings, $key, $maxValue)
    {
        if (isset($newSettings[$key])) {
            $newSettings[$key] = $maxValue < $newSettings[$key] ? $maxValue : $newSettings[$key];
        }
        return $newSettings;
    }
    private function _validateNewLPDir($_validateNewLPDir, $newSettings)
    {
        if (!isset($newSettings[\Traffic\Model\Setting::LP_DIR])) {
            return $newSettings;
        }
        $dir = preg_replace(FOLDER_FILTER, "", $newSettings[\Traffic\Model\Setting::LP_DIR]);
        $newSettings[\Traffic\Model\Setting::LP_DIR] = $dir;
        if ($dir === "www") {
            throw new \Core\Application\Exception\Error("Invalid folder www");
        }
        if (strpos($dir, ".") !== false) {
            throw new \Core\Application\Exception\Error("Invalid relative path");
        }
        return $newSettings;
    }
    private function _getNewApiKey()
    {
        return md5(rand(10000, 999999) . SALT);
    }
    private function _updateInDb($newSettings)
    {
        $settingsRepository = \Traffic\Settings\Repository\SettingsRepository::instance();
        foreach ($newSettings as $key => $value) {
            $setting = $settingsRepository->findByKey($key);
            if (empty($setting)) {
                $this->create(["key" => $key, "value" => $value]);
            } else {
                $this->update($setting, ["value" => $value]);
            }
        }
    }
    private function _warmupCache($_warmupCache, $newSettings, $oldSettings)
    {
        \Traffic\Repository\CachedSettingsRepository::instance()->warmup();
        $cacheStorageChanged = isset($newSettings[\Traffic\Model\Setting::CACHE_STORAGE]) && isset($oldSettings[\Traffic\Model\Setting::CACHE_STORAGE]) && $newSettings[\Traffic\Model\Setting::CACHE_STORAGE] != $oldSettings[\Traffic\Model\Setting::CACHE_STORAGE];
        if ($cacheStorageChanged) {
            \Core\Application\Bootstrap::initCacheService();
            \Core\Application\Bootstrap::initEntityCache();
            \Traffic\CachedData\Repository\CachedDataRepository::instance()->warmup();
        }
    }
    private function _renameLPDir($_renameLPDir, $oldSettings, $newSettings)
    {
        while (isset($oldSettings[\Traffic\Model\Setting::LP_DIR]) && isset($newSettings[\Traffic\Model\Setting::LP_DIR])) {
            $oldLPDir = ROOT . "/" . $oldSettings[\Traffic\Model\Setting::LP_DIR];
            $newLPDir = ROOT . "/" . $newSettings[\Traffic\Model\Setting::LP_DIR];
            if ($oldLPDir !== $newLPDir) {
                $pathInLandingDir = false;
                try {
                    \Component\Landings\LocalFile\LocalFileService::instance()->renameFolder($oldLPDir, $newLPDir, $pathInLandingDir);
                } catch (\Exception $e) {
                    throw new \Core\Application\Exception\Error($e->getMessage());
                }
            }
        }
    }
}

?>