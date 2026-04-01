<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Settings\Controller;

class SettingsController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
        if (!$this->isAdmin()) {
            $this->throwDeny();
        }
        return \Traffic\Settings\Repository\SettingsRepository::instance()->allAsHash($this->getParam("only"));
    }
    public function configAction()
    {
        return \Component\Home\Service\JsConfigService::instance()->get($this->getServerRequest());
    }
    public function findAction()
    {
        return ["key" => $this->getParam("key"), "value" => \Traffic\Repository\CachedSettingsRepository::instance()->get($this->getParam("key"))];
    }
    public function updateAction()
    {
        if (\Traffic\Service\ConfigService::instance()->isDemo()) {
            $this->throwDenyBecauseDemo();
        }
        if (!$this->isPost()) {
            throw new \Core\Application\Exception\Error("Must be post request");
        }
        $newSettings = $this->getPostParams();
        \Traffic\Service\SettingsService::instance()->updateValues($newSettings);
        return \Traffic\Settings\Repository\SettingsRepository::instance()->allAsHash(array_keys($newSettings));
    }
    public function getAuxiliaryDataAction()
    {
        return ["cache_storages" => \Traffic\Cache\CacheFactory::getAvailableStorages(), "draft_data_storages" => \Component\DelayedCommands\Repository\DelayedCommandsStorageRepository::instance()->getAvailableOnly(), "redis_installed" => \Traffic\Redis\Service\RedisStorageService::instance()->redisServerExists(), "av_services" => \Component\Av\Service\AVCheckerService::instance()->getServices(), "link_style_variants" => \Traffic\Settings\Repository\SettingsRepository::instance()->getLinkFormats($this->getServerRequest()->getUri()), "currencies" => \Core\Currency\Repository\CurrenciesRepository::instance()->getCurrencies(), "ts_parameters" => \Traffic\Repository\ParameterRepository::getAvailableParameters()];
    }
    public function changeLanguageAction()
    {
        if (\Traffic\Service\ConfigService::instance()->isDemo()) {
            $this->throwDenyBecauseDemo();
        }
        $new = $this->getParam("new") == \Core\Locale\LocaleService::RU ? \Core\Locale\LocaleService::RU : \Core\Locale\LocaleService::EN;
        \Traffic\Service\SettingsService::instance()->updateValue("language", $new);
        $this->redirect("?");
    }
}

?>