<?php
class Migration_20210404212150_ExpireOldIpSessions extends Migration 
{
    const DESCRIPTION_RU = 'Очистка старых сессий уникальности';

    const DESCRIPTION_EN = 'Clear old uniqueness sessions';

    public static function up()
    {
        if (!\Traffic\Redis\Service\RedisStorageService::instance()->isEnabled()) {
            return ;
        }
        if (!\Traffic\Redis\Service\RedisStorageService::instance()->isRedisAlive()) {
            return ;
        }

        $it = null;
        do {
            $sessionKeys = \Traffic\Redis\Service\RedisStorageService::instance()->scan($it, '*IP_SESSIONS*', 1000);
            if (empty($sessionKeys)) {
                break;
            }
            foreach ($sessionKeys as $key) {
                $count = 1;
                $key = str_replace(\Traffic\Redis\Service\RedisStorageService::instance()->prefix(), '', $key, $count);
                $ttl = \Traffic\Redis\Service\RedisStorageService::instance()->ttl($key);
                // more than 15 years
                if ($ttl > 500000000) {
                    \Traffic\Redis\Service\RedisStorageService::instance()->expire($key, 86400);
                }
            }
        } while ($it);
    }
}
