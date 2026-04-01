<?php

class Migration_20210512173255_AddLpPhpTimeoutSetting extends Migration
{
    const DESCRIPTION_RU = 'Добавление настройки `lp_php_timeout`';

    const DESCRIPTION_EN = 'Add `lp_php_timeout` setting';

    public static function up()
    {
        $prefix = self::getPrefix();
        $value = '5';
        $sql = "INSERT IGNORE INTO {$prefix}settings (`key`, `value`) values ('lp_php_timeout', {$value})";
        self::execute($sql);
    }
}
