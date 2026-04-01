<?php

class Migration_20200907090524_AddRedirectToDomains extends Migration
{
    const DESCRIPTION_RU = 'Добавление redirect';

    const DESCRIPTION_EN = 'Add redirect column';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "ALTER IGNORE TABLE {$prefix}domains ADD COLUMN redirect varchar(255) default 'not'";
        self::silentExecute($sql);
    }
}