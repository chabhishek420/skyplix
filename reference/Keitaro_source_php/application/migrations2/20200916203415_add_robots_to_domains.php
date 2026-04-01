<?php

class Migration_20200916203415_AddRobotsToDomains extends Migration
{
    const DESCRIPTION_RU = 'Добавление is_robots_allowed в domains';

    const DESCRIPTION_EN = 'Add is_robots_allowed to domains';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "ALTER IGNORE TABLE {$prefix}domains ADD COLUMN is_robots_allowed tinyint(1) unsigned DEFAULT 1";
        self::silentExecute($sql);
    }
}