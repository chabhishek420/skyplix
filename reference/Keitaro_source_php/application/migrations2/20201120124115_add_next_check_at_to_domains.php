<?php

class Migration_20201120124115_AddNextCheckAtToDomains extends Migration
{
    const DESCRIPTION_RU = 'Добавление next_check_at в domains';

    const DESCRIPTION_EN = 'Add next_check_at to domains';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "ALTER IGNORE TABLE {$prefix}domains ADD COLUMN next_check_at DATETIME DEFAULT NULL";
        self::silentExecute($sql);
    }
}