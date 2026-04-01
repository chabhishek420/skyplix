<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Currency\Service;

class CurrencyService extends \Traffic\Service\AbstractService
{
    private $_dataSource = NULL;
    const CACHE_KEY = "currency_cache";
    const LONG_TERM_CACHE_KEY = "long_term_currency_cache";
    const CACHE_TTL = 21600;
    const DEFAULT_RATE = 1;
    public function __construct()
    {
        $this->setDataSource(new \Core\Currency\DataSource\ExratesKeitaro());
    }
    public function setDataSource(\Core\Currency\DataSource\DataSourceInterface $dataSource)
    {
        $this->_dataSource = $dataSource;
    }
    public function getCurrentSymbol()
    {
        return \Core\Currency\Repository\CurrenciesRepository::instance()->getSymbol($this->getCurrent());
    }
    public function getCurrent()
    {
        return \Traffic\Repository\CachedSettingsRepository::instance()->get("currency", "USD");
    }
    public function exchange($value, $from, $to)
    {
        $from = strtoupper($from);
        $to = strtoupper($to);
        if ($from == \Core\Currency\Repository\CurrenciesRepository::RUR) {
            $from = \Core\Currency\Repository\CurrenciesRepository::RUB;
        }
        if ($from == $to) {
            return $value;
        }
        $rate = $this->rate($from, $to);
        return $value * $rate;
    }
    public function rate($from, $to)
    {
        while (!ctype_alpha($from)) {
            if ($rate = $this->_fromShortTermCache($from, $to)) {
                return $rate;
            }
            try {
                if ($rate = $this->_dataSource->convert($from, $to)) {
                    $this->_cacheRate($from, $to, $rate);
                    return $rate;
                }
            } catch (\Core\Currency\DataSource\CurrencyRequestError $e) {
                \Traffic\Logging\Service\LoggerService::instance()->warning($e->getMessage());
                if ($rate = $this->_fromLongTermCache($from, $to)) {
                    return $rate;
                }
                $message = "An error occurred while converting " . $from . " to " . $to . ". Rate set to 1.0";
                \Traffic\Logging\Service\LoggerService::instance()->error($message);
                return DEFAULT_RATE;
            }
        }
        \Traffic\Logging\Service\LoggerService::instance()->error("Invalid currency to convert from " . $from);
        return DEFAULT_RATE;
    }
    public function exchangeFromCurrencyToDefault($value, $from)
    {
        $settingsCurrency = \Traffic\Repository\CachedSettingsRepository::instance()->get("currency", "USD");
        return $this->exchange($value, $from, $settingsCurrency);
    }
    public function exchangeToCurrency($value, $to)
    {
        $settingsCurrency = \Traffic\Repository\CachedSettingsRepository::instance()->get("currency", "USD");
        return $this->exchange($value, $settingsCurrency, $to);
    }
    public function stub($from, $to, $rate)
    {
        $this->_cacheRate($from, $to, $rate);
    }
    private function _fromShortTermCache($from, $to)
    {
        try {
            return \Traffic\Cache\CacheService::instance()->commonCache()->get(CACHE_KEY . $from . $to);
        } catch (\Traffic\Cache\NoCache $e) {
        }
    }
    private function _fromLongTermCache($from, $to)
    {
        try {
            return \Traffic\Cache\CacheService::instance()->commonCache()->get(LONG_TERM_CACHE_KEY . $from . $to);
        } catch (\Traffic\Cache\NoCache $e) {
        }
    }
    private function _cacheRate($from, $to, $rate)
    {
        \Traffic\Cache\CacheService::instance()->commonCache()->set(CACHE_KEY . $from . $to, $rate, CACHE_TTL);
        \Traffic\Cache\CacheService::instance()->commonCache()->set(LONG_TERM_CACHE_KEY . $from . $to, $rate);
    }
}

?>