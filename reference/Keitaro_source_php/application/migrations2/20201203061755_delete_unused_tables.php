<?php
class Migration_20201203061755_DeleteUnusedTables extends Migration
{
    const DESCRIPTION_RU = 'Удаление legacy таблиц';

    const DESCRIPTION_EN = 'Delete legacy tables';

    public static function up()
    {
        $tables = ['shards', 'conversions', 'daily_entries', 'command_queue', 'user_campaign_permissions'];

        $prefix = self::getPrefix();

        foreach ($tables as $table) {
            $sql = "DROP TABLE IF EXISTS {$prefix}{$table}";
            self::execute($sql);
        }
    }
}
