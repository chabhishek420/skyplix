<?php
class Migration_20201203061229_AddShareToStreams extends Migration
{
    const DESCRIPTION_RU = 'Добавить weight в streams';

    const DESCRIPTION_EN = 'Add weight to streams';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "ALTER TABLE {$prefix}streams ADD COLUMN weight int UNSIGNED DEFAULT 0";
        self::silentExecute($sql);

        $sql = "UPDATE {$prefix}streams SET weight = position WHERE weight = 0";
        self::silentExecute($sql);
    }
}
