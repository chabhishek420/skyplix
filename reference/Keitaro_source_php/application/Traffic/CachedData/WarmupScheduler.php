<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData;

class WarmupScheduler
{
    const FILE_NAME = "/var/cache/need-warmup";
    public static function filePath()
    {
        return ROOT . FILE_NAME;
    }
    public static function scheduleWarmup()
    {
        touch(WarmupScheduler::filePath());
    }
    public static function done()
    {
        if (is_file(WarmupScheduler::filePath())) {
            unlink(WarmupScheduler::filePath());
        }
    }
    public static function isScheduled()
    {
        $filePath = self::filePath();
        if (file_exists($filePath)) {
            return true;
        }
        return false;
    }
}

?>