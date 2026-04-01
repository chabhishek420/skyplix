<?php

class Migration_20181010122302_RemoveUniqIndexInConversions extends Migration
{
    public const DESCRIPTION_RU = 'Убрать уникальность subid+tid в conversions_2';

    public const DESCRIPTION_EN = 'Remove uniq index from subid+tid in conversions_2';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "ALTER TABLE {$prefix}conversions_2 DROP KEY `sub_id_tid`, ADD KEY `sub_id_tid` (`sub_id`,`tid`)";
        self::silentExecute($sql);
    }
}
