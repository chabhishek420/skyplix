<?php
class Migration_20200408164315_AddDefaultActionAllowed extends Migration
{
    const DESCRIPTION_RU = 'Настройки действий домена по умолчанию';

    const DESCRIPTION_EN = 'Domain default action settings';

    public static function up()
    {
        $prefix = self::getPrefix();
        $sql = "SELECT `value` FROM `{$prefix}settings` WHERE `key` = 'default_action_allowed'";
        $row = self::getDb()->getRow($sql);
        if (empty($row)) {
            $change = "INSERT INTO `{$prefix}settings` (`key`, `value`) VALUES ('default_action_allowed', '1')";
            self::execute($change);
        }
    }
}
