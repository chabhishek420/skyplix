<?php

class Migration_20180825100512_AddStorageSetting extends Migration
{
    public const DESCRIPTION_RU = 'Добавление настройки data_storage';

    public const DESCRIPTION_EN = 'Add setting data_storage';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "INSERT IGNORE INTO {$prefix}settings (`key`, `value`) values ('data_storage', 'mysql')";
        self::execute($sql);
    }
}
