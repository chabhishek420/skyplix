<?php

class Migration_20181012083327_AddPreviousConversionId extends Migration
{
    public const DESCRIPTION_RU = 'Добавление previous_conversion_id в conversions_2';

    public const DESCRIPTION_EN = 'Add previous_conversion_id to conversions_2';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "ALTER TABLE {$prefix}conversions_2 ADD COLUMN previous_conversion_id  int(10) unsigned default null;";
        self::silentExecute($sql);

        $sql = "INSERT INTO {$prefix}settings (`key`, `value`) values ('conversions_previous_conversion_id_exists', 1)";
        self::silentExecute($sql);
    }
}
