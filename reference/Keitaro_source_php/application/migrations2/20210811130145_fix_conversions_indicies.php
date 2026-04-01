<?php

class Migration_20210811130145_FixConversionsIndicies extends Migration
{
    public const DESCRIPTION_RU = 'Восстановление индекса subid+tid в conversions_2';

    public const DESCRIPTION_EN = 'Restore index subid+tid in conversions_2';

    public static function up(?bool $rerun = false): void
    {
        $prefix = self::getPrefix();
        $alias = 'IndexIsThere';
        $sql = "
        SELECT 
            COUNT(1) {$alias} 
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE 
            table_schema=DATABASE() AND 
            table_name='{$prefix}conversions_2' AND 
            index_name='sub_id_tid';
        ";
        $hasIndexResult = self::silentExecute($sql);
        $indexMissing = false;
        foreach ($hasIndexResult as $row) {
            if (isset($row[$alias]) && $row[$alias] == 0) {
                $indexMissing = true;
            }
        }
        if ($indexMissing) {
            $sql = "ALTER TABLE {$prefix}conversions_2 ADD KEY `sub_id_tid` (`sub_id`,`tid`)";
            self::silentExecute($sql);
        }
    }
}
