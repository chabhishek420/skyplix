<?php

class Migration_20201204163520_AddCheckRetriesToDomains extends Migration
{
    const DESCRIPTION_RU = 'Добавление check_retries в domains';

    const DESCRIPTION_EN = 'Add check_retries to domains';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "ALTER IGNORE TABLE {$prefix}domains ADD COLUMN check_retries int(10) unsigned DEFAULT 0";
        self::silentExecute($sql);
    }
}