<?php

class Migration_20210706120502_NullifyUserPassword extends Migration
{
    public const DESCRIPTION_RU = 'Обновлено устаревшее поле password';

    public const DESCRIPTION_EN = 'Updated deprecated field password';

    public static function up()
    {
        $prefix = self::getPrefix();

        $sql = "ALTER TABLE {$prefix}users CHANGE COLUMN `password` `password` varchar(32) DEFAULT NULL";
        self::execute($sql);
    }
}
