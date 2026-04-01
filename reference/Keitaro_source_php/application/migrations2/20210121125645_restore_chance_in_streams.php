<?php
class Migration_20210121125645_RestoreChanceInStreams extends Migration
{
    const DESCRIPTION_RU = 'Восстановление chance в streams';

    const DESCRIPTION_EN = 'Restore chance in streams';

    public static function up()
    {
        $prefix = self::getPrefix();

        $sql = "SHOW COLUMNS FROM `{$prefix}streams` LIKE 'chance'";
        $result = self::getDb()->execute($sql)->getArray();
        if (empty($result)) {
            $sql = "ALTER IGNORE TABLE {$prefix}streams ADD COLUMN chance int default 0";
            self::silentExecute($sql);
        }
    }
}
