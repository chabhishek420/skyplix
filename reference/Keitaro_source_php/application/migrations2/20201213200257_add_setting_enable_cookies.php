<?php
class Migration_20201213200257_AddSettingEnableCookies extends Migration
{
    const DESCRIPTION_RU = 'Включение куки по умолчанию';

    const DESCRIPTION_EN = 'Enable cookies by default';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "SELECT `value` FROM `{$prefix}settings` WHERE `key` = 'cookies_enabled'";
        $row = self::getDb()->getRow($sql);
        if (empty($row)) {
            $change = "INSERT INTO `{$prefix}settings` (`key`, `value`) VALUES ('cookies_enabled', '1')";
            self::execute($change);
        }
    }
}