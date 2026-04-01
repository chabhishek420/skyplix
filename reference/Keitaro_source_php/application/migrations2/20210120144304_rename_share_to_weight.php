<?php
class Migration_20210120144304_RenameShareToWeight extends Migration
{
    const DESCRIPTION_RU = 'Переименовать streams.share на streams.weight';

    const DESCRIPTION_EN = 'Rename streams.share to streams.weight';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "ALTER TABLE {$prefix}streams CHANGE share weight int UNSIGNED DEFAULT 0";
        self::silentExecute($sql);
    }
}
