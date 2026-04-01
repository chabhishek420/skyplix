<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\PruneTask;

class PruneLandingOfferCache implements \Component\PruneTask\PruneTaskInterface
{
    public function getType()
    {
        return \Component\PruneTask\Repository\PruneTaskRepository::GENERAL_TYPE;
    }
    public function prune()
    {
        $path = \Traffic\Cache\CacheService::instance()->lpOfferCache()->getOption(\Traffic\Cache\Cache::CACHE_DIRECTORY);
        $startTime = microtime(true);
        \Traffic\Cache\CleanExpiredService::instance()->cleanCacheFolder($path);
        $elapsed = microtime(true) - $startTime;
        if (300 < $elapsed) {
            \Traffic\Logging\Service\LoggerService::instance()->warning("Clearing old token in files took " . number_format($elapsed) . " sec.");
        }
    }
}

?>