<?php

class Migration_20200907092913_AddSSLDataToDomains extends Migration
{
    const DESCRIPTION_RU = 'Добавление ssl_data';

    const DESCRIPTION_EN = 'Add ssl_data column';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "ALTER IGNORE TABLE {$prefix}domains ADD COLUMN ssl_data text default null";
        self::silentExecute($sql);
    }
}