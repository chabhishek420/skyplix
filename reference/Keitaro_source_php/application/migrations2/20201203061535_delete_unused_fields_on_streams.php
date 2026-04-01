<?php
class Migration_20201203061535_DeleteUnusedFieldsOnStreams extends Migration
{
    const DESCRIPTION_RU = 'Удаление redirect_type, url из streams';

    const DESCRIPTION_EN = 'Delete redirect_type, url from streams';

    public static function up()
    {
        $columns = ['redirect_type', 'url'];
        $prefix = self::getPrefix();

        foreach ($columns as $column) {
            $sql = "ALTER TABLE {$prefix}streams DROP `{$column}`";
            self::silentExecute($sql);
        }
    }
}
