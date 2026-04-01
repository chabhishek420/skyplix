<?php

class Migration_20201204163415_RenamePreviousCheckAtInDomains extends Migration
{
    public const DESCRIPTION_RU = 'Переименовывание previous_check_at в domains';

    public const DESCRIPTION_EN = 'Rename previous_check_at in domains';

    public static function up()
    {
        $prefix = self::getPrefix();

        $sql = "ALTER TABLE {$prefix}domains CHANGE COLUMN previous_check_at next_check_at DATETIME DEFAULT NULL";
        self::silentExecute($sql);

        $sql = "UPDATE {$prefix}domains SET next_check_at = NULL";
        self::silentExecute($sql);
    }
}
