<?php
class Migration_20201227061229_FixShare extends Migration
{
    const DESCRIPTION_RU = 'Исправление share в streams';

    const DESCRIPTION_EN = 'Fix share to streams';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "ALTER TABLE {$prefix}streams CHANGE share share decimal(5,2) UNSIGNED DEFAULT 0"; // 00.00
        self::silentExecute($sql);
    }
}
