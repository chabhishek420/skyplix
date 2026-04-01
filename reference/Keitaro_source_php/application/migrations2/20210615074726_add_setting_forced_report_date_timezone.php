<?php

class Migration_20210615074726_AddSettingForcedReportDateTimezone extends Migration
{
    public const DESCRIPTION_RU = 'Add forced_report_utc_timezone setting';

    public const DESCRIPTION_EN = 'Add forced_report_utc_timezone setting';

    public static function up()
    {
        $prefix = self::getPrefix();
        self::execute("INSERT IGNORE INTO {$prefix}settings (`key`, `value`) values ('forced_report_utc_timezone', '1')");
    }
}
