<?php

class Migration_20210827074848_AddRefValueToLabels extends Migration
{
    const DESCRIPTION_RU = 'Добавление значения Ref\'a в таблицу меток';

    const DESCRIPTION_EN = 'Adding Ref value to labels table';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "ALTER IGNORE TABLE {$prefix}labels 
        MODIFY ref_id int(12) unsigned null,
        ADD COLUMN ref_value varchar(255) null,
        ADD UNIQUE `campaign_name_value` (`campaign_id`, `ref_name`, `ref_value`)";
        self::silentExecute($sql);
    }
}
